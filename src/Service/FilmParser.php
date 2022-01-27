<?php

namespace App\Service;

use App\Entity\Film;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;

class FilmParser extends AbstractController
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;

    }

    /**
     * @param Film $film
     */
    public function getDescription(Film $film): ?Film
    {
        $client = HttpClient::create();
        $omdbApiKey = $this->getParameter('app.apikey');
        $response = $client->request('GET', 'https://www.omdbapi.com/?apikey=' . $omdbApiKey . '&t=' . $film->getTitre());
        $content = $response->toArray();

        if ($content['Response'] == "True") {
            if ($film->getScore()<0) {
                $film->setScore(0);
            }
            if ($film->getScore()>10) {
                $film->setScore(10);
            }
            $film->setDescription($content['Plot']);
            $film->setTitre($content['Title']);
            $film->setNombreVotants(intval(str_replace(",", "",$content['imdbVotes']))+1);
            $film->setScore((((floatval($content['imdbRating'])*($film->getNombreVotants()-1))+$film->getScore()))/$film->getNombreVotants());

            return $film;
        } else {
            return null;
        }
    }
}