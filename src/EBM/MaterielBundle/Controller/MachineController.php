<?php

namespace EBM\MaterielBundle\Controller;

use EBM\KMBundle\Entity\CompetenceUser;
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
                'title' => $reservation->getUser()->getUsername(),
                'start' => str_replace(' ', 'T', $reservation->getDebut()->format('Y-m-d H:i:s')),
                'end' => str_replace(' ', 'T', $reservation->getFin()->format('Y-m-d H:i:s'))
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

        $formBuilder
            ->add('nom', TextType::class)
            ->add('dateAchat', DateType::class, array('data' => new \DateTime('now')))
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

        return $this->render('EBMMaterielBundle:Default/machines:reservationMachine.html.twig', array('form' => $form->createView()));
    }

    public function reservationMachineAction($machine, $debut, $fin, Request $request)
    {
        $resa_machine = new ReservationMachine();

        $formBuilder = $this->get('form.factory')->createBuilder(FormType::class, $resa_machine);

        $listeMachines = array();
        foreach($this->getAllMachines() as $machine)
        {
            $listeMachines[$machine->getNom()] = $machine;
        }


        $formBuilder
            ->add('user', TextType::class, array('disabled' => 'true', 'data' => $this->getUser()))
            /*->add('dateCreation', DateType::class, array(
                'disabled' => 'true',
                'data' => new \DateTime('now'),
                'attr' => array('style' => 'display:none'
                )
            ))*/
            ->add('machine', ChoiceType::class, array(
                'choices' => $listeMachines,
                'data' => 2
            ))
            ->add('debut', DateTimeType::class, array('data' => new \DateTime($debut)))
            ->add('fin', DateTimeType::class, array('data' => new \DateTime($fin)))
            ->add('description', TextareaType::class, array('required' => 'false'))
            ->add('valider', SubmitType::class);


        $form = $formBuilder->getForm();

        if ($request->isMethod('POST')){
            $form->handleRequest($request);

            $resa_machine->setUser($this->getUser());

            if($form->isValid()){
                $em = $this->getDoctrine()->getManager();
                $em->persist($resa_machine);
                $em->flush();

                $request->getSession()->getFlashBag()->add('message', 'Réservation bien enregistrée.');

                return $this->redirectToRoute('ebm_materiel_machines');
            }
        }

        return $this->render('EBMMaterielBundle:Default/machines:reservationMachine.html.twig', array('form' => $form->createView()));

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

    public function getAllCompetences()
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('EBMKMBundle:CompetenceUser');

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