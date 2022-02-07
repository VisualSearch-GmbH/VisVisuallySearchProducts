<?php declare(strict_types=1);
/*
 * (c) VisualSearch GmbH <office@visualsearch.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with the source code.
 */

namespace Vis\VisuallySearchProducts\Administration\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Framework\Cache\Annotation\HttpCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Vis\VisuallySearchProducts\Util\SwRepoUtils;
use Vis\VisuallySearchProducts\Service\VisuallySearchApiServiceInterface;

/**
 * @RouteScope(scopes={"administration"})
 */
class VisuallySearchController extends AbstractController
{
    /**
     * @var VisuallySearchApiServiceInterface
     */
    private $visuallySearchApiService;

    /**
     * @param VisuallySearchApiServiceInterface $visuallySearchApiService
     */
    public function __construct(
        VisuallySearchApiServiceInterface $visuallySearchApiService
    ) {
        $this->visuallySearchApiService = $visuallySearchApiService;
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

        $message = $this->visuallySearchApiService->similarCompute($products);

        // return message
        return new JsonResponse(["code" => 200, "message" => "Info VisVisuallySearchProducts: " . $message]);
    }

    /**
     * @Route("/api/_action/vis/sim/api_key_verify", name="api.action.vis.sim.api_key_verify", methods={"POST"})
     */
    public function apiKeyVerify(Request $request, Context $context): JsonResponse
    {
        return new JsonResponse(['success' => $this->visuallySearchApiService->verifyApiKey()]);
    }
}
