<?php

namespace App\Http\Controllers\Api;

use App\Content;
use App\ContentCategory;
use App\ContentLikeLog;
use App\ContentLikeCount;
use App\ContentViewCount;
use App\ContentViewLog;
use App\Customer;
use App\CustomerCategory;
use App\TemporaryToken;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    public function getContentListByCategory($category_id, $order='desc')
    {
        return Content::where('category_id', $category_id)->where('is_active', 1)->orderBy('id', $order)->get();
    }

    public function getView($id, Request $request)
    {
        $accountStatus = [];
        //check if customer is using trial mode
        if (isset($request->temporaryToken) && strlen($request->temporaryToken) == 32) {
            $token = $request->temporaryToken;
            $customer_trial_token = TemporaryToken::where('token', $token)->first();
            if (is_null($customer_trial_token)) {
                return response([
                    'success'   => false,
                    'code'      => 2111, //trial users
                    'message'   => 'Invalid token.'
                ]);
            }
            $accountStatus['isTrial'] = true;
            $accountStatus['remainingTrialContents'] = config('general.allowed_view_content_count') - $customer_trial_token->viewed_contents_count;
            //check if requested content has been seen before or not
            $content_view_log = ContentViewLog::where('content_id', $id)->where('temporary_token', $token)->first();
            if (is_null($content_view_log)) {
                if ($customer_trial_token->viewed_content_counts < config('general.allowed_view_content_count')) {
                    //increase viewed contents count
                    DB::connection('mongodb')->table('temporary_tokens')->where('token', $token)->increment('viewed_contents_count');
                    //store viewed content log
                    ContentViewLog::create(['content_id' => $id, 'temporary_token' => $token]);
                } else {
                    return response([
                        'success' => false,
                        'code' => 2200,
                        'message' => 'Your trial has been ended.'
                    ]);
                }
            }
        } else {
            $registered_users_token = new \stdClass(); //retrieve from db and check if is still valid @TODO
            if (is_null($registered_users_token)) {
                return response([
                    'success'   => false,
                    'code'      => 2110, //registered users
                    'message'   => 'Invalid token.'
                ]);
            }

            ContentViewLog::where('content_id', $id)->where('customer_id', $registered_users_token->customer_id)->firstOrCreate(); //@TODO test

            $contentLikeLog = ContentLikeLog::where('content_id', $id)->where('customer_id', $registered_users_token->customer_id)->first(); //@TODO test

            if (!is_null($contentLikeLog)) {
                $userHasLikedThisContent = true;
            }
        }

        $success = true;
        $content = Content::where('id', $id)->where('is_active', 1)->first();
        if (isset($userHasLikedThisContent) && $userHasLikedThisContent) {
            $content->user_has_liked_this_content = true;
        }

        return compact('success', 'content', 'accountStatus');
    }

    public function getUserFeed($account_id, $catalog_id, $offset=0)
    {
        //get all categories in requested catalog
        $catalog_categories = ContentCategory::where('catalog_id', $catalog_id)->get(['id'])->implode('id', ',');
        $catalog_categories = (strpos($catalog_categories, ',')) ? explode(',', $catalog_categories) : str_split($catalog_categories, 1);

        //get all user categories in requested catalog
        $user_categories    = CustomerCategory::whereHas('customer', function($q) use($account_id) {
                                                       $q->where('account_id', $account_id);
                                                })
                                                ->whereIn('category_id', $catalog_categories)
                                                ->get(['category_id'])
                                                ->implode('category_id', ',');

        $user_categories = (strpos($user_categories, ',')) ? explode(',', $user_categories) : str_split($user_categories, 1);

        //check if user has any favorite category or not, if not warn customer to select at least one category
        if (count($user_categories) == 0 || strlen($user_categories[0]) == 0) {
            $result = [
                    'success'   => false,
                    'code'      => 2100,
                    'message'   => "برای استفاده از امکانات ابتدا علاقه‌مندی‌هات رو از طریق زیر مشخص کن.",
                    ];
            
            return compact('result');
        }

        //get all contents in customer categories
        /*$take = $offset*self::FEED_CONTENTS_CHUNK_SIZE;
        $skip = ($offset > 1) ? $take-self::FEED_CONTENTS_CHUNK_SIZE : 0;*/
        $contents = Content::whereIn('category_id', $user_categories)
                            ->where('is_active', '1')
                            ->orderBy('updated_at', 'desc')
                            ->skip($offset)
                            ->take(config('general.feed_contents_chunk_size'))
                            ->get();

        return compact('contents');
    }

    public function postAddViewCount(Request $request)
    {
        $content_id = $request->contentId;
//        $like_count = ContentViewCount::where('content_id', $content_id)->first();

        /*if (is_null($like_count)) {
            ContentViewCount::create(['content_id' => $content_id, 'count' => rand(99, 999), 'original_count' => 1]);
        } else {*/
            $view_count = DB::connection('mongodb')
                                ->table('content_view_counts')
                                ->where('content_id', $content_id);
            $view_count->increment('count');
            $view_count->increment('original_count');
//        }

        return response(['success' => 'true']);
    }

    public function postLike(Request $request)
    {
        $contentId      = $request->contentId;
        $customer       = Customer::where('account_id', $request->accountId)->first();
        $likeByCustomer = ContentLikeLog::where('content_id', $request->contentId)->where('customer_id', $customer->id)->first();

        if (is_null($likeByCustomer)) {
            ContentLikeLog::create(['content_id' => $contentId, 'customer_id' => $customer->id]);
            /*$likeCount = ContentLikeCount::where('content_id', $contentId)->first();
            if (is_null($likeCount)) {
                ContentLikeCount::create(['content_id' => $contentId, 'count' => rand(99, 999), 'original_count' => 1]);
            } else {*/
                $addLike = DB::connection('mongodb')->table('like_counts')->where('content_id', $contentId);
                $addLike->increment('count');
                $addLike->increment('original_count');
//            }
        } else {
            $likeByCustomer->delete();
            $subtractLike = DB::connection('mongodb')->table('like_counts')->where('content_id', $contentId);
            $subtractLike->decrement('count');
            $subtractLike->decrement('original_count');
        }

        return response(['success' => 'true']);
    }
}
