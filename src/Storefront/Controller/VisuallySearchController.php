<?php declare(strict_types=1);
/*
 * (c) VisualSearch GmbH <office@visualsearch.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with the source code.
 */

namespace Vis\VisuallySearchProducts\Storefront\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Vis\VisuallySearchProducts\Api\Exception\VisuallySearchApiException;
use Vis\VisuallySearchProducts\Service\HelperServiceInterface;
use Vis\VisuallySearchProducts\Service\VisuallySearchApiServiceInterface;

/**
 * @RouteScope(scopes={"storefront"})
 */
class VisuallySearchController extends StorefrontController
{
    /**
     * @var VisuallySearchApiServiceInterface
     */
    private $visuallySearchApiService;

    /**
     * @var HelperServiceInterface
     */
    private $helperService;

    /**
     * @param VisuallySearchApiServiceInterface $visuallySearchApiService
     * @param HelperServiceInterface $helperService
     */
    public function __construct(
        VisuallySearchApiServiceInterface $visuallySearchApiService,
        HelperServiceInterface $helperService
    ) {
        $this->visuallySearchApiService = $visuallySearchApiService;
        $this->helperService = $helperService;
    }

    /**
     * @Route("/vis/search", name="frontend.vis.search.page", methods={"POST"})
     */
    public function search(Request $request, Context $context): RedirectResponse
    {
        $image = $request->files->get('image');
        $base64 = $this->helperService->imageToBase64($image);
        try {
            $productIds = $this->visuallySearchApiService->searchSingle($base64);
        } catch (VisuallySearchApiException $exception) {
            if ($exception->getStatusCode() === Response::HTTP_FORBIDDEN) {
                $this->addFlash(self::DANGER, $this->trans('visVisuallySearchProducts.invalidApiCredentialsErrorMessage'));
            }
            $productIds = [];
        }
        return $this->redirectToRoute('frontend.search.page', [
            'vis' => $productIds,
            'search' => $image->getClientOriginalName()
        ]);
    }
}
