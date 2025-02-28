<?php
namespace Mageplaza\BetterProductReviewsGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory as VoteCollectionFactory;
use Mageplaza\BetterProductReviews\Model\ResourceModel\Review\CollectionFactory;


class ProductRatingSummary implements ResolverInterface
{
    protected $reviewCollectionFactory;
    protected $voteCollectionFactory;

    public function __construct(
        CollectionFactory $reviewCollectionFactory,
        VoteCollectionFactory $voteCollectionFactory
    ) {
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->voteCollectionFactory   = $voteCollectionFactory;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $productId = $args['productId'];

        $reviewCollection = $this->reviewCollectionFactory->create()
            ->addFieldToFilter('entity_pk_value', $productId);

        $totalRating  = 0;
        $reviewCount  = $reviewCollection->getSize();
        $ratingSpread = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        if ($reviewCount > 0) {
            foreach ($reviewCollection as $review) {
                $rating = $review->getAvgValue();
                if ($rating === null) {
                    $rating = $this->getRatingFromVotes($review->getReviewId());
                }

                $totalRating += $rating;
                if (isset($ratingSpread[$rating])) {
                    $ratingSpread[$rating]++;
                }
            }
        }

        $averageRating = $reviewCount > 0 ? $totalRating / $reviewCount : 0;

        $formattedRatingSpread = [];
        for ($i = 1; $i <= 5; $i++) {
            $formattedRatingSpread[] = [
                'star'  => $i,
                'count' => $ratingSpread[$i],
            ];
        }

        return [
            'averageRating' => round($averageRating, 2),
            'ratingSpread'  => $formattedRatingSpread,
        ];
    }

    /**
     * Lấy giá trị rating từ bảng rating_option_vote nếu avg_value bị null
     */
    private function getRatingFromVotes($reviewId)
    {
        $voteCollection = $this->voteCollectionFactory->create()
            ->addFieldToFilter('review_id', $reviewId);

        $totalVotes = 0;
        $voteCount  = 0;

        foreach ($voteCollection as $vote) {
            $totalVotes += $vote->getPercent() / 20;
            $voteCount++;
        }

        return $voteCount > 0 ? round($totalVotes / $voteCount) : 0;
    }
}
