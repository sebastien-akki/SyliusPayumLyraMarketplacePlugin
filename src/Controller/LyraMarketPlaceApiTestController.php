<?php

namespace Akki\SyliusPayumLyraMarketplacePlugin\Controller;

use Akki\SyliusPayumLyraMarketplacePlugin\Service\LyraMarketplaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LyraMarketPlaceApiTestController extends AbstractController
{

    /**
     * @param LyraMarketplaceService $lyraMarketplace
     */
    public function listMarketPlace(LyraMarketplaceService  $lyraMarketplace)
    {
        $marketplacesList = $lyraMarketplace->getMarketplaceList() ;
        $sellers = $lyraMarketplace->getSellers() ;

        dump($sellers, $marketplacesList);

        if (!empty($marketplacesList) && !empty($marketplacesList["results"])) {
            $marketplace = $marketplacesList["results"][0] ;
            dump($marketplace);
        }
    }

}
