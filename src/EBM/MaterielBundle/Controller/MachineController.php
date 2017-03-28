<?php

namespace EBM\MaterielBundle\Controller;

use EBM\KMBundle\Entity\Tag;
use EBM\MaterielBundle\Entity\Machine;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use EBM\MaterielBundle\Entity\ReservationMachine;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;

class MachineController extends Controller
{
    public function indexAction()
    {
        return $this->render('EBMMaterielBundle:Default:index.html.twig');
    }

    public function machineAction()
    {
        return $this->render('EBMMaterielBundle:Default/machines:machine.html.twig');
    }

    public function selectionMachineAction()
    {
        return $this->render('EBMMaterielBundle:Default/machines:selectionMachinePlanning.html.twig', array('machines' => $this->getAllMachines()));
    }

    public function planningMachineAction($machineId)
    {
        $reservations = $this->getAllReservationsByMachineId($machineId);


        $jsonEvents = '[';

        foreach($reservations as $reservation)
        {
            $json = json_encode(array(
                'id' => $reservation->getId(),
                'title' => $reservation->getUser()->getUsername(),
                'start' => str_replace(' ', 'T', $reservation->getDebut()->format('Y-m-d H:i:s')),
                'end' => str_replace(' ', 'T', $reservation->getFin()->format('Y-m-d H:i:s')),
                'backgroundColor' => '#e53935'
            ));

            $jsonEvents = $jsonEvents.$json;

            if($reservation !== end($reservations))
                $jsonEvents = $jsonEvents.',';
        }

        $jsonEvents = $jsonEvents.']';



        /* $json = json_encode(array('title' => 'testJson', 'start' => '2017-03-03')); */
        return $this
            ->render('EBMMaterielBundle:Default/machines:planningMachine.html.twig',
                array(
                    'machine' => $this->getMachine($machineId),
                    'events' => $reservations,
                    'jsonEvents' => $jsonEvents,
                    'machines' => $this->getAllMachines()
                )
            );
    }

    public function ajoutMachineAction(Request $request)
    {
        $newMachine = new Machine();


        $formBuilder = $this->get('form.factory')->createBuilder(FormType::class, $newMachine);
        $listeTags = array();
        foreach($this->get('ebmkm.tag')->getTagsByType('TYPE_MACHINE') as $tag)
        {
            $listeTags[$tag->getName()] = $tag;
        }

        $formBuilder
            ->add('nom', TextType::class, array('label' => 'Nom de la machine'))
            ->add('dateAchat', DateType::class, array('label' => 'Date d\'achat de la machine', 'attr' => array('data-plugin' => 'datepicker','class'=>'datepicker')))
            ->add('tags', ChoiceType::class, array('label' => 'Tags associés', 'choices' => $listeTags, 'attr' => array('data-plugin' => 'select2', 'multiple' => 'true')))
            ->add('valider', SubmitType::class);

        $form = $formBuilder->getForm();

        if ($request->isMethod('POST')){
            $form->handleRequest($request);

            if($form->isValid()){

                $em = $this->getDoctrine()->getManager();
                $em->persist($newMachine);
                $em->flush();

                $request->getSession()->getFlashBag()->add('message', 'Machine enregistrée.');

                return $this->redirectToRoute('ebm_materiel_machines');
            }
        }

        return $this->render('EBMMaterielBundle:Default/machines:ajoutMachine.html.twig',
            array(  'form' => $form->createView(),
//                    'tags' => $this->get('ebmkm.tag')->getTagsByType('TYPE_MACHINE'),
            ));
    }

    public function reservationMachineAction($machine, $debut, $fin)
    {
        if($fin == null)
        {
            $heure = intval(substr($debut, 11, 2)) + 2;
            $heureString = strval($heure);
            if($heure < 10)
            {
                $heureString = '0'.$heureString;
            }
            $fin = substr($debut, 0,11).$heureString.substr($debut,13);
        }

        return $this->render('EBMMaterielBundle:Default/machines:reservationMachine.html.twig',
            array(
                'selectedMachine' => $machine,
                'debut' => $debut,
                'fin' => $fin,
                'projets' => $this->getUser()->getProjects(),
                'machines' => $this->getAllMachines(),
                'user' => $this->getUser()
            ));
    }


    public function reservationSubmitAction(Request $request){

        $data = $request->getContent();

        return $this->render('EBMMaterielBundle:Default:testpost.html.twig', array('message' => $data));
    }

    public function machineAdded(){

    }



    /*
     * useful methods
     */

    public function getMachine($machineId)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('EBMMaterielBundle:Machine');

        return $repository->find($machineId);
    }

    public function getAllTags()
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('EBMKMBundle:Tag');

        return $repository->findAll();
    }
    public function getAllMachines()
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('EBMMaterielBundle:Machine');

        return $repository->findAll();
    }

    public function getAllReservationsByMachineId($machineId)
    {
        $repositoryReservation = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('EBMMaterielBundle:ReservationMachine');

        return $repositoryReservation->findBy(array('machine' => $this->getMachine($machineId)));
    }

}
