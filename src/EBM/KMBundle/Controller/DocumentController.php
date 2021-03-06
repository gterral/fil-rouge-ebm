<?php
/**
 * Created by PhpStorm.
 * User: huber
 * Date: 27/02/2017
 * Time: 08:56
 */

namespace EBM\KMBundle\Controller;


use EBM\KMBundle\Entity\Document;
use EBM\KMBundle\Entity\DocumentHistory;
use EBM\KMBundle\Entity\DocumentRepository;
use EBM\KMBundle\Entity\EvaluationDocument;
use EBM\KMBundle\Entity\Post;
use EBM\KMBundle\Entity\Topic;
use EBM\KMBundle\Form\DocumentType;
use EBM\KMBundle\Form\EvaluationDocumentType;
use EBM\KMBundle\Form\PostType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\File;


class DocumentController extends Controller
{

    /**
     * List all documents
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(){

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT d
            FROM EBMKMBundle:Document d
            WHERE d.active = :status
            ORDER BY d.date DESC'
        )->setParameter('status', true);

        $documents = $query->getResult();

        return $this->render('EBMKMBundle:Documents:index.html.twig', array("documents" => $documents));
    }

    /**
     * Create a new document
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function uploadAction(Request $request){

        $document = new Document();
        $documentHistory = new DocumentHistory();

        // On récupère les différents tags pour les passer dans le formulaire.
        $tags = $this->getDoctrine()->getRepository('EBMKMBundle:Tag')->findAll();
        $form = $this->createForm(DocumentType::class, $document, array(
            'tags' => $tags
        ));
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            // Le créateur du document est l'utilisateur courrant.
            $user = $this->getUser();
            $documentHistory->setAuthor($user);
            $document->setHistory($documentHistory);

            // On persiste le document et son historique nouvellement créé.
            $em = $this->getDoctrine()->getManager();
            $em->persist($document);
            $em->flush();

            return $this->redirect($this->generateUrl('ebmkm_document_detail', array( "id" => $document->getId() )));

        }

        return $this->render('EBMKMBundle:Documents:upload.html.twig', array(
            'form' => $form->createView(),
        ));

    }

    /**
     * Finds and displays a document based on its id
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function detailAction($id, Request $request){
        /** @var Document $document */
        $document = $this->getDoctrine()->getRepository('EBMKMBundle:Document')->find($id);
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        /*
         * Cette partie charge le fil de commentaires sur le document.
         * S'il n'y en a pas, il sera créé lorsque l'utilisateur commentera pour la première fois.
         * Une fois le message posté, la page est raffraichie.
         *
         * Le topic créé aura le nom du document suivi de '- Commentaires'.
         * Une description est générée automatiquement.
         *
         * //TODO : Bouger le texte de la description dans un fichier avec tous les textes.
         *
         */
        if($document->getHistory()->getCommentTopic()){
            $topic = $document->getHistory()->getCommentTopic();
        }
        else{
            $topic = new Topic();
            $topic
                ->setTitle($document->getName() . ' - Commentaires')
                ->setCreator($user);
            $topic->setDescription("Ceci est le fil de discussion relatif au document ' " . $document->getName() . '.');

            $document->getHistory()->setCommentTopic($topic);
        }

        $post = new Post();
        $post->setTopic($topic);
        $post->setAuthor($user);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        /*
         * Le fil de commentaire n'est créé et complété par les posts que si le nouveau post est bien formé.
         */
        if($form->isSubmitted() && $form->isValid())
        {
            $em->persist($topic);
            $em->persist($post);
            $em->persist($document);
            $em->flush();
            return $this->redirectToRoute('ebmkm_document_detail', array('id' => $document->getId()));
        }

        /*
         * Charge les notes données au document. On en calcule d'abord la moyenne.
         * Si aucune note n'a été donnée, on donne une moyenne de -1 pour pouvoir afficher un message adapté.
         *
         * L'évaluation d'un document se fait via un formulaire utilisant un slider.
         * Une fois ce formulaire posté, la page est raffraichie.
         */
        if(sizeof($document->getHistory()->getEvaluations()) > 0){
            $moyenne = 0;
            foreach ($document->getHistory()->getEvaluations() as $evaluation){
                $moyenne += $evaluation->getValue();
            }
            $moyenne = $moyenne/sizeof($document->getHistory()->getEvaluations());
        }
        else{
            $moyenne = -1;
        }



        /*
         * Evaluation du document par l'utilisateur courrant.
         */

        $evaluation = new EvaluationDocument();
        $evaluationForm = $this->createForm(EvaluationDocumentType::class, $evaluation);
        $evaluationForm->handleRequest($request);
        if($evaluationForm->isSubmitted() && $evaluationForm->isValid()){

            /** @var EvaluationDocument $personalEvaluation */
            $personalEvaluation = $this->getDoctrine()->getRepository('EBMKMBundle:EvaluationDocument')
                ->findOneBy(['author' => $this->getUser()]);
            /*
             * Si une évaluation existe déjà; on change simplement sa valeur.
             * Sinon, on persist l'entité.
             */
            if($personalEvaluation){
                $personalEvaluation->setValue($evaluation->getValue());
                $em->flush();
            }
            else{
                $evaluation->setAuthor($user);
                $evaluation->setDocument($document->getHistory());
                $em->persist($evaluation);
                $em->flush();
            }

            return $this->redirectToRoute('ebmkm_document_detail', array('id' => $document->getId()));
        }

        return $this->render('EBMKMBundle:Documents:detail.html.twig', array(
            'document' => $document,
            'grade' => $moyenne,
            'form' => $form->createView(),
            'evaluationForm' => $evaluationForm->createView()
        ));
    }

    /**
     * Displays a form to update a document, based on its id
     * This form can only be edited by the author of the document.
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function updateAction($id, Request $request){
        /**
         * On récupère un clone de la dernière version du document.
         *
         * @var Document $document
         */
        $oldDocument = $this->getDoctrine()->getRepository('EBMKMBundle:Document')->find($id);
        $updateDocument = clone $oldDocument;

        $tags = $this->getDoctrine()->getRepository('EBMKMBundle:Tag')->findAll();

        /*
         * On récupère tout d'abord le chemin du document.
         * On peut ensuite récupérer le document en lui-même, et l'affecter au champ 'File' du Document.
         */
        $helper = $this->get('vich_uploader.templating.helper.uploader_helper');
        $path = $helper->asset($oldDocument, 'file');
        $kernel_root_dir = $this->getParameter('kernel.root_dir');
        $file = new File\File( $kernel_root_dir . '/../web' . $path);
        $updateDocument->setFile($file);

        $deleteForm = $this->createDeleteForm($updateDocument);
        $editForm = $this->createForm(DocumentType::class, $updateDocument, array(
            'tags' => $tags
        ));
        $editForm->handleRequest($request);

        /*
         * Si les données sont validées et que l'utilisateur connecté est bien l'auteur du document :
         * - On récupère le nouveau formulaire, et on applique les modifications.
         * - On rajoute le nouveau document dans l'historique.
         */
        if ($editForm->isSubmitted()
            && $editForm->isValid()
            && $this->getUser() == $oldDocument->getHistory()->getAuthor()) {

            $oldDocument->setActive(false);
            $updateDocument->setDate(new \DateTime());

            if(!$updateDocument->getFile()){
                $updateDocument->setFile($oldDocument->getFile());
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($updateDocument);
            $em->flush();

            return $this->redirectToRoute('ebmkm_document_detail', array('id' => $updateDocument->getId()));
        }

        return $this->render('EBMKMBundle:Documents:update.html.twig', array(
            'document' => $updateDocument,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a document, all its history, grades and comments.
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $id)
    {
        /** @var Document $document */
        $document = $this->getDoctrine()->getRepository("EBMKMBundle:Document")->find($id);

        $form = $this->createDeleteForm($document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->getUser() == $document->getHistory()->getAuthor()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($document);
            $em->flush($document);
        }

        return $this->redirectToRoute('ebmkm_document_index');
    }

    /**
     * Creates a form to delete a document entity
     *
     * @param Document $document The document entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Document $document)
    {
        return $this->createFormBuilder()
            ->add('submit', SubmitType::class, array('label' => 'Supprimer le document'))
            ->setAction($this->generateUrl('ebmkm_document_delete', array('id' => $document->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

}