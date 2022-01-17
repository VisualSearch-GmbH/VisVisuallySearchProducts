<?php

declare(strict_types=1);
/*
 * (c) VisualSearch GmbH <office@visualsearch.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with the source code.
 */

namespace Vis\VisuallySearchProducts\Storefront\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Vis\VisuallySearchProducts\Util\ApiRequest;
use Vis\VisuallySearchProducts\Util\SwHosts;
use Vis\VisuallySearchProducts\Util\SwRepoUtils;

/**
 * @RouteScope(scopes={"api"})
 */
class VisuallySearchController extends AbstractController
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @Route("/api/_action/vis/sim/status_version", name="api.action.vis.sim.status_version", methods={"POST"})
     */
    public function statusVersion(Request $request, Context $context): JsonResponse
    {
        $repository = $this->container->get('plugin.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', "VisVisuallySearchProducts"));

        $search = $repository->search($criteria, \Shopware\Core\Framework\Context::createDefaultContext());

        foreach ($search->getEntities()->getElements() as $key => $entity) {
            return new JsonResponse(["code" => 200, "message" => "Info VisVisuallySearchProducts: V" . $entity->getVersion()]);
        }

        return new JsonResponse(["code" => 200, "message" => "Info VisVisuallySearchProducts: version unknown"]);
    }

    /**
     * @Route("/api/_action/vis/sim/update_categories", name="api.action.vis.sim.update_categories", methods={"POST"})
     */
    public function updateCategories(Request $request, Context $context): JsonResponse
    {
        // get product repository
        $productRepository = $this->container->get('product.repository');

        $swRepo = new SwRepoUtils();

        // search criteria
        $criteria = new Criteria();
        $criteria->addAssociation('cover');
        $criteria->addAssociation('crossSellings');

        // search repository
        $products = $swRepo->searchProducts($productRepository, $criteria);
        if (empty($products)) {
            return new JsonResponse(["code" => 200, "message" => "Info VisVisuallySearchProducts: no products"]);
        }

        // retrieve hosts and keys
        $retrieveHosts = new SwHosts($this->container->get('sales_channel.repository'));
        $systemHosts = $retrieveHosts->getLocalHosts();;

        // submit update request
        $api = new ApiRequest($this->container->get('recommend_similar_products_logs.repository'));
        $message = $api->update(
            $this->systemConfigService->get('VisVisuallySearchProducts.config.apiKey'),
            $products,
            $systemHosts
        );

        // return message
        return new JsonResponse(["code" => 200, "message" => "Info VisVisuallySearchProducts: " . $message]);
    }

    /**
     * @Route("/api/_action/vis/sim/api_key_verify", name="api.action.vis.sim.api_key_verify", methods={"POST"})
     */
    public function apiKeyVerify(Request $request, Context $context): JsonResponse
    {
        // verify api key
        $api = new ApiRequest($this->container->get('recommend_similar_products_logs.repository'));
        $message = $api->verify($this->systemConfigService->get('VisVisuallySearchProducts.config.apiKey'));

        if ($message == "API key ok") {
            return new JsonResponse(['success' => true]);
        } else {
            return new JsonResponse(['success' => false]);
        }
    }
}
