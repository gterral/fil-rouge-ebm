<?php

namespace EBM\GDPBundle\Controller;

use EBM\GDPBundle\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use EBM\UserInterfaceBundle\Entity\Project;

class ProjectController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @ParamConverter("project",options={"mapping": {"code":"code"}})
     */
    public function viewTasksAction(Project $project)
    {
        // On return la vue avec la liste des tâches
        return $this->render('EBMGDPBundle:Project:index.html.twig',
            array('listTasks' => $project->getTasks(),
                'project' => $project
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @ParamConverter("project",options={"mapping": {"code":"code"}})
     */
    public function viewDeliverablesAction(Project $project)
    {
        return $this->render('EBMGDPBundle:Project:index.html.twig');
    }
}
