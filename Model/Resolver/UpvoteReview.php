<?php
namespace Mageplaza\BetterProductReviewsGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mageplaza\BetterProductReviews\Api\ProductReviewsRepositoryInterface;
use Psr\Log\LoggerInterface;

class UpvoteReview implements ResolverInterface
{
    protected $productReviewsRepository;
    protected $_logger;

    public function __construct(
        ProductReviewsRepositoryInterface $productReviewsRepository,
        LoggerInterface $logger
    ) {
        $this->productReviewsRepository = $productReviewsRepository;
        $this->_logger = $logger;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $reviewId = $args['reviewId'];

        $review = $this->productReviewsRepository->getReviewById($reviewId);

        $this->_logger->info('Review Array:', ['review' => $review]);

        if (empty($review)) {
            throw new \Exception(__('Review not found.'));
        }

        $review = $review[$reviewId];

        $helpfulCount = $review->getMpBprHelpful() + 1;
        $review->setMpBprHelpful($helpfulCount);

        $review->save();

        return [
            'review_id' => $review->getReviewId(),
            'mp_bpr_helpful' => $review->getMpBprHelpful(),
        ];
    }
}
