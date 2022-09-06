<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Entity\Article;
use App\Entity\Category;
use App\Form\ArticleFormType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/tableau-de-bord', name: 'show_dashboard', methods: ['GET'])]
    public function showDashboard(EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findBy(['deletedAt' => null]);

        $categories = $entityManager->getRepository(Category::class)->findBy(['deletedAt' => null]);

        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('admin/show_dashboard.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'users' => $users,
        ]);
    }

    #[Route('/voir-les-archives', name: 'show_archives', methods: ['GET'])]
    public function showArchives(EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAllArchived();
        $articles = $entityManager->getRepository(Article::class)->findAllArchived();
        $users = $entityManager->getRepository(User::class)->findAllArchived();

        return $this->render('admin/show_archives.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'users' => $users,
        ]);
    }

    #[Route('/creer-un-article', name: 'create_article', methods:['GET', 'POST'])]
    public function createarticle(Request $request, SluggerInterface $slugger, ArticleRepository $repository): Response
    {
        $article = new Article();

        $form = $this->createForm(ArticleFormType::class, $article)->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $article->setCreatedAt(new DateTime());
            $article->setUpdatedAt(new DateTime());
            $article->setAlias($slugger->slug($article->getTitle()));
            $article->setAuthor($this->getUser());

            /* @var UploadedFile $photo */
            $photo = $form->get('photo')->getData();

            if ($photo) {
                $this->handleFile($article, $photo, $slugger);
            }

            $repository->add($article, true);

            $this->addFlash('success', 'L\'article a bien été ajouté, il est en ligne !');
            return $this->redirectToRoute('show_dashboard');
        }

        return $this->render('admin/form/article.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/modifier-un-article/{id}', name: 'update_article', methods:['GET', 'POST'])]
    public function updateArticle(Article $article,Request $request, SluggerInterface $slugger, ArticleRepository $repository): Response
    {
        $originalPhoto = $article->getPhoto();

        $form = $this->createForm(ArticleFormType::class, $article, [
            'photo' => $originalPhoto
        ])->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $article->setUpdatedAt(new DateTime());
            $article->setAlias($slugger->slug($article->getTitle()));
            $article->setAuthor($this->getUser());

            /* @var UploadedFile $photo */
            $photo = $form->get('photo')->getData();

            if ($photo) {
                $this->handleFile($article, $photo, $slugger);
            }
            else {
                $article->setPhoto($originalPhoto);
            }

            $repository->add($article, true);

            $this->addFlash('success', 'L\'article a bien été modifié !');
            return $this->redirectToRoute('show_dashboard');
        }

        return $this->render('admin/form/article.html.twig', [
            'form' => $form->createView(),
            'article' => $article
        ]);
    }

    ///////////////////////////////////////////////////////////////// PRIVATE FUNCTION /////////////////////////////////////////////////////////////

    private function handleFile(Article $article, UploadedFile $photo, SluggerInterface $slugger): void
    {
        $extension = '.' . $photo->guessExtension();

        $safeFilename = $slugger->slug($article->getTitle());

        $newFilename = $safeFilename . '_' . uniqid() . $extension;

        try {

            $photo->move($this->getParameter('uploads_dir'), $newFilename);
            $article->setPhoto($newFilename);

        } catch (FileException $exception) {

            $this->addFlash('warning', 'La photo du article ne s\'est pas importée avec succès. Veuillez réessayer en modifiant le article.');
            // return $this->redirectToRoute('add_produit');
        }
    }
}

