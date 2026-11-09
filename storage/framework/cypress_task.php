<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();


      use App\Models\User;
      use App\Models\ProductCategory;
      use App\Models\Product;
      use App\Models\ProductVariant;
      use App\Models\ForumPost;
      use App\Models\Conversation;
      use App\Models\Agreement;
      use Illuminate\Support\Facades\Hash;

      $seller = User::updateOrCreate(
        ['email' => 'seller_c2@markettrust.test'],
        [
          'username' => 'seller_c2',
          'password_hash' => 'password123',
          'email_verified_at' => now(),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $buyer = User::updateOrCreate(
        ['email' => 'buyer_c2@markettrust.test'],
        [
          'username' => 'buyer_c2',
          'password_hash' => 'password123',
          'email_verified_at' => now(),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $category = ProductCategory::firstOrCreate(
        ['category_name' => 'Voucher Game Cycle 2'],
        ['is_active' => true]
      );

      $product = Product::firstOrCreate(
        ['product_name' => 'Valorant Points Cycle 2', 'category_id' => $category->category_id],
        ['is_active' => true]
      );

      $variant = ProductVariant::firstOrCreate(
        ['variant_name' => '1120 VP Fast Send', 'product_id' => $product->product_id],
        ['is_active' => true]
      );

      $post = ForumPost::firstOrCreate(
        ['title' => 'WTS: 1120 VP Valorant Legal Fast'],
        [
          'author_id' => $seller->user_id,
          'category_code' => 'WTS',
          'variant_id' => $variant->variant_id,
          'content' => 'Dijual 1120 VP via riot id.',
          'price_offer' => 120000,
          'status' => 'ACTIVE',
        ]
      );

      $conv = Conversation::firstOrCreate([
        'participant_one_id' => min($buyer->user_id, $seller->user_id),
        'participant_two_id' => max($buyer->user_id, $seller->user_id),
        'post_id' => $post->post_id,
      ]);

      // Create draft agreement Rev #1
      $agr = Agreement::updateOrCreate(
        ['buyer_id' => $buyer->user_id, 'seller_id' => $seller->user_id, 'variant_id' => $variant->variant_id],
        [
          'created_by' => $buyer->user_id,
          'item_title' => '1120 VP Valorant Points Resmi',
          'item_description' => 'Pengiriman via Riot ID',
          'quantity' => 1,
          'base_price' => 120000,
          'fee_type' => 'EXCLUDE',
          'fee_amount' => 6000,
          'buyer_total' => 126000,
          'seller_net' => 120000,
          'delivery_method' => 'DIRECT_CHAT',
          'delivery_within_minutes' => 30,
          'revision_no' => 1,
          'status' => 'DRAFT',
          'buyer_accepted_at' => now(),
          'seller_accepted_at' => null,
        ]
      );

      $conv->update(['agreement_id' => $agr->agreement_id]);

      echo json_encode([
        'conversation_id' => $conv->conversation_id,
        'variant_id' => $variant->variant_id,
        'buyer_username' => $buyer->username,
        'seller_username' => $seller->username,
      ]);
    
