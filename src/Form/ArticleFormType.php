<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ArticleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 3,
                        'max' => 100,
                    ])
                ]
            ])

            ->add('subtitle', TextType::class, [
                'label' => 'Sous-titre',
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 3,
                        'max' => 100,
                    ])
                ]
            ])

            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
            ])

            ->add('photo', FileType::class, [
                'label' => 'Photo du produit',
                'data_class' => null,
                'constraints' => [
                    new Image([
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Les formats autorisés sont : .jpg, .png',
                        'maxSize' => '3M',
                        'maxSizeMessage' => 'La taille maximum autorisé est de {{ limit }} {{ suffix }} => {{ name }} : {{ size }} {{ suffix }}'
                    ]),
                ],
                'help' => 'Fichiers autorisés .jpg .png'
            ])

            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie de l\'article',
                # On utilise le queryBuilder pour récupérer uniquement les catégories non archivées
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.deletedAt IS NULL')
                        ->orderBy('c.name', 'ASC');
                }
            ])

            ->add('submit', SubmitType::class, [
                // 'label' => 'Ajouter',
                'label' => $options['photo'] ? 'Modifier' : "Ajouter",
                'validate' => false,
                'attr' => [
                    'class' => 'd-block mx-auto my-3 col-6 btn btn-primary'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'allow_file_upload' => true, // Pour autoriser l'upload de fichiers
            'photo' => null,
        ]);
    }
}
