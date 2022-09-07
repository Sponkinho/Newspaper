<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Form\ChangePasswordFormType;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/profile')]
class UserController extends AbstractController
{
    #[Route('/voir-mon-compte', name: 'show_profile', methods:['GET', 'POST'])]
    public function showUser(EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findBy(['author' => $this->getUser()]);

        return $this->render('user/show_profile.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/changer-mon-mot-de-passe', name:'change_password', methods:['GET', 'POST'])]
    public function changePassword(Request $request, UserRepository $repository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(ChangePasswordFormType::class)
        ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            # Récupération en BDD du $user, cela nous permet d'utiliser les méthodes de notre entité User (ex: $user->setUpdatedAt())
            $user = $repository->find($this->getUser());

            # Etapes pour vérifier le password:
                # 1 - Un nouvel input => ChangePassword
                # 2 - Recupération de la valeur de l'input
                # 3 - Hasher le currentPassword pour comparaison avec celui en BDD
                # 4 - Condition de vérification
                # 5 - Si la condition est vérifiée, alors on exécute la suite.

            // $currentPasswordHashed = $passwordHasher->hashPassword(
            //     $user, $form->get('currentPassword')->getData()
            // );

            // Vérification du MDP
            $currentPassword = $form->get('currentPassword')->getData();

            // isPasswordValid Pour vérifier les deuc valeurs hashés
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('warning', 'Le mot de passe actuel n\'est pas valide');
                return $this->redirectToRoute('show_profile');
            }
            
            $user->setUpdatedAt(new DateTime());

            # Variabilisation de la valeur de l'input plainPassword de notre formulaire
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($passwordHasher->hashPassword(
                $user, $plainPassword
            ));

            $repository->add($user, true);

            $this->addFlash('success', 'Votre mot de passe a bien été changé');
            return $this->redirectToRoute('show_profile');

        }

        return $this->render('security/change_password.html.twig', [
            'form' => $form->createView()
        ]);
    }

}

