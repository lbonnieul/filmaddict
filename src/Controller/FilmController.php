<?php

namespace App\Controller;

use App\Service\FilmParser;
use App\Entity\Film;
use App\Form\Film1Type;
use App\Repository\FilmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Validator\Constraints\File;

/**
 * @Route("/")
 */
class FilmController extends AbstractController
{
    /**
     * @Route("/", name="film_index", methods={"GET"})
     */
    public function index(FilmRepository $filmRepository): Response
    {
        return $this->render('film/index.html.twig', [
            'films' => $filmRepository->findBy(
                array(),
                array('score' => 'DESC', 'titre' => 'ASC')
            ),
        ]);
    }

    /**
     * @Route("/film/new", name="film_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager, FilmParser $filmParser): Response
    {
        $film = new Film();
        $form = $this->createForm(Film1Type::class, $film);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $film=$filmParser->getDescription($film);
            if ($film != null) {
                $entityManager->persist($film);
                $entityManager->flush();
            } else {
                return $this->renderForm('film/add.html.twig', [
                    'film' => $film,
                    'form' => $form,
                    'err_add' => true
                ]);
            }


            return $this->redirectToRoute('film_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('film/add.html.twig', [
            'film' => $film,
            'form' => $form,
            'err_add' => false
        ]);
    }

    /**
     * @Route("/film/{id}", name="film_show", methods={"GET"})
     */
    public function show(Film $film): Response
    {

        return $this->render('film/film.html.twig', [
            'film' => $film,
            'err_del' => false
        ]);

    }

    /**
     * @Route("/film/{id}", name="film_delete", methods={"POST"})
     */
    public function delete(Request $request, Film $film, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$film->getId(), $request->request->get('_token'))) {
            if ($_POST['password'] == $this->getParameter('app.admin_code')) {
                $entityManager->remove($film);
                $entityManager->flush();
            } else {
                return $this->render('film/film.html.twig', [
                    'film' => $film,
                    'err_del' => true
                ]);
            }

        }

        return $this->redirectToRoute('film_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/film/add/csv", name="film_add_csv")
     */
    public function addCsv(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer, ManagerRegistry $doctrine): Response
    {

        return $this->render('film/add_csv.html.twig');
    }
}

