<?php

namespace CM\CMBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use CM\CMBundle\Entity\EntityUser;
use CM\CMBundle\Entity\UserTagRepository;
use CM\CMBundle\Form\DataTransformer\UserToIntTransformer;
use CM\CMBundle\Form\DataTransformer\TagsToTextTransformer;

class EntityUserType extends AbstractType
{
    protected $options;

    public function __construct(array $options = array())
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
    }
        
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add($builder->create('user', 'hidden')->addModelTransformer(new UserToIntTransformer($options['em'])))
            ->add('admin', 'checkbox', array(
                'required' => false,
                'label' => 'Make admin'
            ));
        if (in_array('ROLE_ADMIN', $options['roles'])) {
            $builder->add('status', 'choice', array(
                    'choices' => array(
                        EntityUser::STATUS_PENDING => 'Pending',
                        EntityUser::STATUS_ACTIVE => 'Active',
                        EntityUser::STATUS_REQUESTED => 'Requested',
                        EntityUser::STATUS_REFUSED_ADMIN => 'Refused by an admin',
                        EntityUser::STATUS_REFUSED_ENTITY_USER => 'Refused by an entity user',
                        EntityUser::STATUS_FOLLOWING => 'Following'
                    )
                ));
        }
        $builder->add($builder->create('entityUserTags', 'hidden', array(
                    'attr' => array('tags' => array_reduce($options['tags'], function($carry, $a) { return $carry.(is_null($carry) ? '' : ';').$a->getId().','.$a; })),
                    'label' => 'Roles'
                ))->addModelTransformer(new TagsToTextTransformer($options['tags'], 'CM\CMBundle\Entity\EntityUserTag')));
        if ($options['is_admin']) {
            $builder->add('notification');
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data instanceof EntityUser && $data->getStatus() == EntityUser::STATUS_REQUESTED) {
                $form->add('status', 'choice', array(
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => array(
                        EntityUser::STATUS_REQUESTED => 'requested',
                        EntityUser::STATUS_ACTIVE => 'accept',
                        EntityUser::STATUS_REFUSED_ADMIN => 'refuse',
                    )
                ));
            }
         });
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'roles' => array(),
            'tags' => array(),
            'locale' => 'en',
            'locales' => array('en'),
            'data_class' => 'CM\CMBundle\Entity\EntityUser'
        ));

        $resolver->setRequired(array(
            'em',
            'roles',
            'tags'
        ));

        $resolver->setAllowedTypes(array(
            'em' => 'Doctrine\Common\Persistence\ObjectManager',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'cm_cmbundle_entityuser';
    }
}
