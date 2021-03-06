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
    public function viewDeliverablesAction(Project $project)
    {
        // Check whether the user has access to project or not. If not, this method will throw a 404 exception.
        $this->get("ebmgdp.utilities.permissions")->isGrantedAccessForProject($project,$this->getUser());

        $repository_type= $this->getDoctrine()->getRepository('EBMGDPBundle:DocumentTypeProject');
        $listtypes = $repository_type->findAll();

        return $this->render('EBMGDPBundle:Project:index.html.twig',
            array('listDeliverables' => $project->getDeliverables(),
                'project' => $project,
                'listTypeDeliverables' => $listtypes,
            )
        );
    }
}
