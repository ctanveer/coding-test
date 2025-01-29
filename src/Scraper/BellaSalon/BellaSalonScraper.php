<?php

namespace App\Scraper\BellaSalon;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class BellaSalonScraper
{
  protected $url = "https://go.booker.com/location/BellaSalonAustin/service-menu";
  private $httpClient;
  private $output;

  public function __construct(HttpClientInterface $client)
  {
    $this->httpClient = $client;
    $this->output = new ConsoleOutput();
  }

  public function scrape(): array
  {
    $pantherClient = Client::createChromeClient();
    $response = $pantherClient->request('GET', $this->url);
    $pantherClient->waitFor('.service-menu-content');

    $this->output->writeln("Extracting services");
    $serviceItems = $this->extractServices($response);
    
    $this->writeToCSV($serviceItems);

    return $serviceItems;
  }


  private function extractServices(Crawler $crawler): array
  {
    $services = [];
    
    $crawler->filter('.service-menu-category')->each(function (Crawler $categoryNode) use (&$services) {
        //Category name
        $categoryName = $categoryNode->filter('.group-container-header-sticky .custom-color-border-top-color')->text('');
        
        $categoryNode->filter('.menu-group-items .service-card-item')->each(function (Crawler $serviceNode) use (&$services, $categoryName) {
            $services[] = [
                'category' => $categoryName,
                'name' => $serviceNode->filter('.card__title')->text(''),
                'duration' => $serviceNode->filter('.card__info span')->first()->text(''),
                'price' => $serviceNode->filter('.info-card-price-bold')->text(''),
                'description' => $serviceNode->filter('.card__description .lt-line-clamp')->text('')
            ];
        });
    });

    return $services;
  }


  private function writeToCSV(array $services, string $filename = 'bella_salon_services.csv'): void
  {
    $directory = 'var/data';
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    
    $csvFilePath = $directory . '/' . $filename;
    $csvFile = fopen($csvFilePath, 'w');
    
    fprintf($csvFile, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($csvFile, array_keys($services[0]));
    
    // Write data
    foreach ($services as $service) {
        fputcsv($csvFile, $service);
    }
    
    fclose($csvFile);
    
    $this->output->writeln("CSV file created successfully at: " . $csvFilePath);
    $this->output->writeln("Total services added: " . count($services));
  }
}