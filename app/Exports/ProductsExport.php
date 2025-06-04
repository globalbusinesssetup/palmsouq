<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Config;

class ProductsExport implements FromCollection, WithHeadings
{
    protected $lang;

    public function __construct($lang)
    {
        $this->lang = $lang;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = Product::query();
        $lang = $this->lang;

        if ($lang) {
            $query = $query->with(['product_langs' => function($q) use ($lang) {
                $q->where('lang', $lang);
            }]);
        }

        $query = $query->with([
            'product_categories.category',
            'product_collections.collection',
            'brand',
            'tax_rules',
            'shipping_rule',
            'bundle_deal',
            'product_images',
            'product_inventories'
        ]);

        $products = $query->get();

        return $products->map(function ($product) use ($lang) {
            // Get categories
            $categories = $product->product_categories->map(function ($pc) use ($lang) {
                if ($lang && $pc->category->category_langs) {
                    return $pc->category->category_langs->where('lang', $lang)->first()->title ?? $pc->category->title;
                }
                return $pc->category->title;
            })->join(',');

            // Get collections
            $collections = $product->product_collections->map(function ($pc) use ($lang) {
                if ($lang && $pc->collection->product_collection_langs) {
                    return $pc->collection->product_collection_langs->where('lang', $lang)->first()->title ?? $pc->collection->title;
                }
                return $pc->collection->title;
            })->join(',');

            // Get additional images
            $additionalImages = $product->product_images->pluck('image')->toArray();
            $additionalImages = array_pad($additionalImages, 8, ''); // Pad to 8 images

            // Get product data based on language
            $productData = $lang ? $product->product_langs->first() : $product;

            // Get brand title
            $brandTitle = '';
            if ($product->brand) {
                if ($lang && $product->brand->brand_langs) {
                    $brandTitle = $product->brand->brand_langs->where('lang', $lang)->first()->title ?? $product->brand->title;
                } else {
                    $brandTitle = $product->brand->title;
                }
            }

            // Get tax rule title
            $taxRuleTitle = '';
            if ($product->tax_rules) {
                if ($lang && $product->tax_rules->tax_rule_langs) {
                    $taxRuleTitle = $product->tax_rules->tax_rule_langs->where('lang', $lang)->first()->title ?? $product->tax_rules->title;
                } else {
                    $taxRuleTitle = $product->tax_rules->title;
                }
            }

            // Get shipping rule title
            $shippingRuleTitle = '';
            if ($product->shipping_rule) {
                if ($lang && $product->shipping_rule->shipping_rule_langs) {
                    $shippingRuleTitle = $product->shipping_rule->shipping_rule_langs->where('lang', $lang)->first()->title ?? $product->shipping_rule->title;
                } else {
                    $shippingRuleTitle = $product->shipping_rule->title;
                }
            }

            // Get bundle deal title
            $bundleDealTitle = '';
            if ($product->bundle_deal) {
                if ($lang && $product->bundle_deal->bundle_deal_langs) {
                    $bundleDealTitle = $product->bundle_deal->bundle_deal_langs->where('lang', $lang)->first()->title ?? $product->bundle_deal->title;
                } else {
                    $bundleDealTitle = $product->bundle_deal->title;
                }
            }

            // Get stock from inventory if available, otherwise use product stock
            // Ensure stock is never null and always >= 0
            $stock = 0;
            if ($product->product_inventories && $product->product_inventories->isNotEmpty()) {
                $inventoryStock = $product->product_inventories->sum('quantity');
                $stock = is_numeric($inventoryStock) ? max(0, $inventoryStock) : 0;
            } else {
                $productStock = $product->stock;
                $stock = is_numeric($productStock) ? max(0, $productStock) : 0;
            }

            // Ensure numeric values are properly formatted
            $offered = is_numeric($product->offered) ? max(0, $product->offered) : 0;
            $selling = is_numeric($product->selling) ? max(0, $product->selling) : 0;
            $warranty = is_numeric($product->warranty) ? max(0, $product->warranty) : 0;
            $refundable = is_numeric($product->refundable) ? max(0, $product->refundable) : 0;

            return [
                'categories' => $categories,
                'brand' => $brandTitle,
                'supplier_name' => '', // Added as per import structure
                'title' => $productData->title ?? '',
                'slug' => $product->slug,
                'supplier_item_code' => $product->supplier_item_code,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'unit' => $productData->unit ?? '',
                'stock' => (string)$stock, // Convert to string to ensure it's not blank
                'offered' => (string)$offered,
                'status' => $product->status ?? 1,
                'description' => $productData->description ?? '',
                'overview' => $productData->overview ?? '',
                'specifications' => $productData->specifications ?? '',
                'weight' => $product->weight ?? '',
                'dimension' => $product->dimension ?? '',
                'meta_title' => $productData->meta_title ?? '',
                'meta_description' => $productData->meta_description ?? '',
                'selling' => (string)$selling,
                'warranty' => (string)$warranty,
                'refundable' => (string)$refundable,
                'main_image' => $product->image ?? '',
                'image_2' => $additionalImages[0] ?? '',
                'image_3' => $additionalImages[1] ?? '',
                'image_4' => $additionalImages[2] ?? '',
                'image_5' => $additionalImages[3] ?? '',
                'image_6' => $additionalImages[4] ?? '',
                'image_7' => $additionalImages[5] ?? '',
                'image_8' => $additionalImages[6] ?? '',
                'banner' => $product->banner_image ?? '',
                'video' => $product->video ?? '',
                'video_thumb' => $product->video_thumb ?? '',
                'tags' => $product->tags ?? '',
                'tax_rule' => $taxRuleTitle,
                'shipping_rule' => $shippingRuleTitle,
                'badge' => $productData->badge ?? '',
                'bundle_deal' => $bundleDealTitle,
                'collections' => $collections,
                'product_id' => $product->id,
            ];
        });
    }

    /**
    * @return array
    */
    public function headings(): array
    {
        return [
            'Categories',
            'Brand',
            'Supplier Name',
            'Title',
            'Slug',
            'Supplier Item Code',
            'Sku',
            'Barcode',
            'Unit',
            'Stock',
            'Offered',
            'Status',
            'Description',
            'Overview',
            'Specificatons',
            'Weight KG',
            'Dimension',
            'Meta title',
            'Meta description',
            'Selling',
            'Warranty',
            'Refundable',
            'Image -main',
            'Image2',
            'image3',
            'image4',
            'image5',
            'image 6',
            'image 7',
            'image 8',
            'Banner',
            'Video',
            'Video thumb',
            'Tags',
            'Tax rule',
            'Shipping rule',
            'Badge',
            'Bundle deal',
            'Collections',
            'Product Id'
        ];
    }
}
