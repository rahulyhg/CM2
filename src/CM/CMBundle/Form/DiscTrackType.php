<?php

namespace CM\CMBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DiscTrackType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('number')
            ->add('title')
            ->add('composer')
            ->add('movement')
            ->add('artists')
            ->add('duration', 'time', array(
                'with_seconds' => true,
                'widget' => 'single_text',
            ))
            ->add('audioFile', 'file', array('required' => false))
            ->add('extract', 'choice', array(
                'required' => false,
                'choices'   => array(false => 'Complete track', true => 'Extract')
            ));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CM\CMBundle\Entity\DiscTrack'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'cm_cmbundle_disctrack';
    }
}
