<?php

namespace EBM\SocialNetworkBundle\Form;

use Core\UserBundle\Entity\User;
use EBM\UserInterfaceBundle\Repository\ProjectRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddPublicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $options['user'];

        if ($user != null){
            $builder
                ->add('content', TextareaType::class)

                ->add('tags', EntityType::class ,  array(
                    'class' => 'EBMKMBundle:Tag',
                    'choice_label' => 'name',
                    'required'=> false,
                    'multiple' => true,

                ))

                ->add('projects', EntityType::class ,  array(
                    'class' => 'EBMUserInterfaceBundle:Project',
                    'choice_label' => 'name',
                    'required'=> false,
                    'multiple' => true,
                    'query_builder' => function (ProjectRepository $er) use ($user) {
                        return $er->createQueryBuilder('projects')
                            ->join('projects.members', 'usr')
                            ->where('usr.id = :myUserId')
                            ->setParameter('myUserId',$user->getId());
                    }
                ))
                ->add('save', SubmitType::class);
        }

    }




    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'EBM\SocialNetworkBundle\Entity\Publication',
            'user' => null
        ));
    }
}


