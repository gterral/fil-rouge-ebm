<?php
/**
 * Created by PhpStorm.
 * User: huber
 * Date: 27/02/2017
 * Time: 08:56
 */

namespace EBM\KMBundle\Controller;


use EBM\KMBundle\Entity\Document;
use EBM\KMBundle\Entity\EvaluationDocument;
use EBM\KMBundle\Entity\Post;
use EBM\KMBundle\Entity\Topic;
use EBM\KMBundle\Form\DocumentType;
use EBM\KMBundle\Form\EvaluationDocumentType;
use EBM\KMBundle\Form\PostType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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

        $documents = $this->getDoctrine()->getRepository('EBMKMBundle:Document')->findAll();

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

        // On récupère les différents tags pour les passer dans le formulaire.
        $tags = $this->getDoctrine()->getRepository('EBMKMBundle:Tag')->findAll();
        $form = $this->createForm(DocumentType::class, $document, array(
            'tags' => $tags
        ));
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            /** @var UploadedFile $file */
            $file = $document->getFile();

            $fileName = md5(uniqid()).'.'.$file->guessExtension();

            $file->move(
                $this->getParameter('files_directory'),
                $fileName
            );

            // replace the file by its name
            $document->setFile($fileName);

            // The document's author is the current user
            $user = $this->getUser();
            $document->setAuthor($user);

            // Persist the newly created document
            $em = $this->getDoctrine()->getManager();
            $em->persist($document);
            $em->flush();

            return $this->redirect($this->generateUrl('ebmkm_document_index'));

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
         *  Cette partie charge le fil de commentaires sur le document.
         * S'il n'y en a pas, il sera créé avec le premier commentaire.
         * Une fois le message posté, la page est raffraichie.
         */
        if($document->getCommentTopic()){
            $topic = $document->getCommentTopic();
        }
        else{
            $topic = new Topic();
            $topic
                ->setTitle($document->getName())
                ->setCreator($user);
            $document->setCommentTopic($topic);
        }

        $post = new Post();
        $post->setTopic($topic);
        $post->setAuthor($user);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

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
        if(sizeof($document->getEvaluations()) > 0){
            $moyenne = 0;
            foreach ($document->getEvaluations() as $evaluation){
                $moyenne += $evaluation->getValue();
            }
            $moyenne = $moyenne/sizeof($document->getEvaluations());
        }
        else{
            $moyenne = -1;
        }

        $personalEvaluation = $this->getDoctrine()->getRepository('EBMKMBundle:EvaluationDocument')
            ->findBy(['author' => $this->getUser(), 'document' => $document]);

        $evaluation = new EvaluationDocument();
        $evaluationForm = $this->createForm(EvaluationDocumentType::class, $evaluation);
        $evaluationForm->handleRequest($request);
        if($evaluationForm->isSubmitted() && $evaluationForm->isValid()){
            $evaluation->setAuthor($user);
            $evaluation->setDocument($document);
            $em->persist($evaluation);
            $em->flush();
            return $this->redirectToRoute('ebmkm_document_detail', array('id' => $document->getId()));
        }

        return $this->render('EBMKMBundle:Documents:detail.html.twig', array(
            'document' => $document,
            'grade' => $moyenne,
            'form' => $form->createView(),
            'personalEvaluation' => $personalEvaluation,
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
        $document = $this->getDoctrine()->getRepository('EBMKMBundle:Document')->find($id);
        $tags = $this->getDoctrine()->getRepository('EBMKMBundle:Tag')->findAll();

        $deleteForm = $this->createDeleteForm($document);
        $editForm = $this->createForm(DocumentType::class, $document, array(
            'tags' => $tags
        ));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('ebmkm_document_detail', array('id' => $document->getId()));
        }

        return $this->render('EBMKMBundle:Documents:update.html.twig', array(
            'document' => $document,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));

    }

    /**
     * Deletes a document.
     *
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        /** @var Document $document */
        $document = $this->getDoctrine()->getRepository("EBMKMBundle:Document")->find($id);

        $form = $this->createDeleteForm($document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
            ->setAction($this->generateUrl('ebmkm_document_delete', array('id' => $document->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

}