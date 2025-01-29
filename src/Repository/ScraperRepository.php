<?php

namespace App\Repository;

use App\Scraper\BellaSalon\BellaSalonScraper;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class ScraperRepository implements ScraperRepositoryInterface
{
  private $httpClient;
  public function __construct(HttpClientInterface $httpClient)
  {
    $this->httpClient = $httpClient;
  }

  public function findAll(): array
  {
    return [
      "BellaSalon" => new BellaSalonScraper($this->httpClient),
    ];
  }
}
