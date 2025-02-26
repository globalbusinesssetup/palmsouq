<?php

namespace App\Imports;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\AttributeValueLang;
use App\Models\Brand;
use App\Models\BrandLang;
use App\Models\BundleDeal;
use App\Models\BundleDealLang;
use App\Models\Cart;
use App\Models\Category;
use App\Models\CategoryLang;
use App\Models\CollectionWithProduct;
use App\Models\Helper\Utils;
use App\Models\InventoryAttribute;
use App\Models\OrderedProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCollection;
use App\Models\ProductCollectionLang;
use App\Models\ProductImage;
use App\Models\ProductImageAttribute;
use App\Models\ProductLang;
use App\Models\ShippingRule;
use App\Models\ShippingRuleLang;
use App\Models\TaxRuleLang;
use App\Models\TaxRules;
use App\Models\UpdatedInventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductsImport implements ToCollection
{

    protected $lang;

    public function __construct($lang)
    {
        $this->lang = $lang;
    }


    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(Collection $rows)
    {
        $lang = $this->lang;

        $adminId = Auth::user()->id;
        // Skip the first row (header/title row)
        $data = $rows->skip(1);


        if ($lang) {

            $brands = Brand::leftJoin('brand_langs as br',
                function ($join) use ($lang) {
                    $join->on('br.brand_id', '=', 'brands.id');
                    $join->where('br.lang', $lang);
                })->select('brands.id', 'br.title');


            $productCollections = ProductCollection::leftJoin('product_collection_langs as pcl',
                function ($join) use ($lang) {
                    $join->on('pcl.product_collection_id', '=', 'product_collections.id');
                    $join->where('pcl.lang', $lang);
                })->select('product_collections.id', 'pcl.title');


            $taxRules = TaxRules::leftJoin('tax_rule_langs as tr',
                function ($join) use ($lang) {
                    $join->on('tr.tax_rule_id', '=', 'tax_rules.id');
                    $join->where('tr.lang', $lang);
                })->select('tax_rules.id', 'tr.title');


            $shippingRules = ShippingRule::leftJoin('shipping_rule_langs as sr',
                function ($join) use ($lang) {
                    $join->on('sr.shipping_rule_id', '=', 'shipping_rules.id');
                    $join->where('sr.lang', $lang);
                })->select('shipping_rules.id', 'sr.title');


            $bundleDeals = BundleDeal::leftJoin('bundle_deal_langs as bd',
                function ($join) use ($lang) {
                    $join->on('bd.bundle_deal_id', '=', 'bundle_deals.id');
                    $join->where('bd.lang', $lang);
                })->select('bundle_deals.id', 'bd.title');

            $categories = Category::leftJoin('category_langs as cl',
                function ($join) use ($lang) {
                    $join->on('cl.category_id', '=', 'categories.id');
                    $join->where('cl.lang', $lang);
                })
                ->select('categories.id', 'cl.title');

            $attrValues = AttributeValue::leftJoin('attribute_value_langs as avl',
                function ($join) use ($lang) {
                    $join->on('avl.attribute_value_id', '=', 'attribute_values.id');
                    $join->where('avl.lang', $lang);
                })
                ->select('attribute_values.id', 'avl.title');

        } else {
            $productCollections = ProductCollection::get();
            $brands = Brand::get();
            $taxRules = TaxRules::get();
            $shippingRules = ShippingRule::get();
            $bundleDeals = BundleDeal::get();
            $categories = Category::get();
            $attrValues = AttributeValue::get();
        }


        $productCollectionsArr = [];
        foreach ($productCollections as $i) {
            $productCollectionsArr[$i->title] = $i->id;
        }

        $brandsArr = [];
        foreach ($brands as $i) {
            $brandsArr[$i->title] = $i->id;
        }

        $taxRulesArr = [];
        foreach ($taxRules as $i) {
            $taxRulesArr[$i->title] = $i->id;
        }

        $shippingRulesArr = [];
        foreach ($shippingRules as $i) {
            $shippingRulesArr[$i->title] = $i->id;
        }

        $bundleDealsArr = [];
        foreach ($bundleDeals as $i) {
            $bundleDealsArr[$i->title] = $i->id;
        }

        $categoriesArr = [];
        foreach ($categories as $i) {
            $categoriesArr[$i->title] = $i->id;
        }

        $attrValuesArr = [];
        foreach ($attrValues as $i) {
            $attrValuesArr[$i->title] = $i->id;
        }

        // Process the data as needed
        foreach ($data as $row) {
            
            // skip rows doesn't contains title
            // if (!isset($row['title']) || empty($row['title'])) {
            //     continue;
            // }

            if (count($row) != 40) {
                throw new \Exception(__('lang.invalid_bulk', [], $lang));
            }

            $slug = isset($row[4]) ? trim($row[4]) : null;
            
            // generate a slug if doesn't exist
            if(empty($slug)) {
                    $slug = Str::slug($row[3]);
            }
            // generate a unique slug name, if already exist
            $prod = Product::where('slug', $slug)->first();
            $count = 1;
            while ($prod) {
                $slug = $slug . '-' . $count;
                $count++;
                $prod = Product::where('slug', $slug)->first();
            }



            $trimmedBrand = strtolower(trim($row[1]));
            $lowercaseBrandsArr = array_change_key_case($brandsArr, CASE_LOWER);

            if ($lang) {

                if (!key_exists(trim($row[31]), $taxRulesArr)) {
                    $tr = TaxRules::create([
                        'type' => Config::get('constants.priceType.FLAT'),
                        'admin_id' => $adminId,
                        'title' => ""
                    ]);

                    TaxRuleLang::create([
                        'tax_rule_id' => $tr->id, 'title' => trim($row[31]), 'lang' => $lang
                    ]);

                    $taxRulesArr[trim($row[31])] = $tr->id;
                }


                

                if (!array_key_exists($trimmedBrand, $lowercaseBrandsArr)) {
                    $br = Brand::create([
                        'admin_id' => $adminId,
                        'title' => "",
                    ]);

                    BrandLang::create([
                        'brand_id' => $br->id, 'title' => trim($row[1]), 'lang' => $lang
                    ]);

                    $lowercaseBrandsArr[trim(strtolower($row[1]))] = $br->id;
                }

                if (!key_exists(trim($row[32]), $shippingRulesArr)) {
                    $sr = ShippingRule::create([
                        'admin_id' => $adminId,
                        'title' => "",
                    ]);

                    ShippingRuleLang::create([
                        'shipping_rule_id' => $sr->id, 'title' => trim($row[32]), 'lang' => $lang
                    ]);

                    $shippingRulesArr[trim($row[32])] = $sr->id;
                }

                if (!key_exists(trim($row[34]), $bundleDealsArr)) {
                    $bd = BundleDeal::create([
                        'admin_id' => $adminId,
                        'title' => "",
                        'free' => 1,
                        'buy' => 1
                    ]);

                    BundleDealLang::create([
                        'bundle_deal_id' => $bd->id, 'title' => trim($row[34]), 'lang' => $lang
                    ]);

                    $bundleDealsArr[trim($row[34])] = $bd->id;
                }

                $prodData = [
                    'title' => "",
                    'badge' => "",
                    'unit' => "",
                    'description' => "",
                    'overview' => "",
                    'specifications' => "",
                    'weight' => "",
                    'dimention' => "",
                    'meta_title' => "",
                    'meta_description' => ""
                ];

            } else {

                if (!key_exists(trim($row[31]), $taxRulesArr)) {
                    $tr = TaxRules::create([
                        'type' => Config::get('constants.priceType.FLAT'),
                        'admin_id' => $adminId,
                        'title' => trim($row[31])
                    ]);
                    $taxRulesArr[$tr->title] = $tr->id;
                }
                
                Log::info('trimmedBrand inside else condition', ['trimmedBrand' => $trimmedBrand]);
                if (!array_key_exists($trimmedBrand, $lowercaseBrandsArr)) {
                    Log::info('trimmedBrand', ['trimmedBrand' => $row[1]]);
                    $br = Brand::create([
                        'admin_id' => $adminId,
                        'title' => trim($row[1])
                    ]);
                    $lowercaseBrandsArr[trim(strtolower($br->title))] = $br->id;
                }

                if (!key_exists(trim($row[32]), $shippingRulesArr)) {
                    $sr = ShippingRule::create([
                        'admin_id' => $adminId,
                        'title' => trim($row[32])
                    ]);
                    $shippingRulesArr[$sr->title] = $sr->id;
                }

                if (!key_exists(trim($row[34]), $bundleDealsArr)) {
                    $bd = BundleDeal::create([
                        'admin_id' => $adminId,
                        'title' => trim($row[34]),
                        'free' => 1,
                        'buy' => 1
                    ]);
                    $bundleDealsArr[$bd->title] = $bd->id;
                }

                $prodData = [
                    'title' => $row[3],
                    'badge' => $row[33],
                    'unit' => $row[8],
                    'description' => $row[12],
                    'overview' => $row[13],
                    'specifications' => $row[14],
                    'weight' => $row[15],
                    'dimention' => $row[16],
                    'meta_title' => $row[17],
                    'meta_description' => $row[18],
                ];
            }


            $productImageName = trim($row[22]);
            if(Utils::isUploadable($productImageName)) {
                $productImageName = Utils::copyImageFromUrl($productImageName, 'product');
            }else{
                $productImageName = Utils::searchImageInStorage($productImageName);
            }

            $productBannerName = trim($row[30]);
            if(Utils::isUploadable($productImageName)) {
                $productBannerName = Utils::copyImageFromUrl($productImageName, 'product');
            }


            $productVideoName = trim($row[31]);
            if(Utils::isUploadable($productVideoName)) {
                $productVideoName = Utils::copyImageFromUrl($productVideoName, 'product');
            }


            $productVideoThumb = trim($row[32]);
            if(Utils::isUploadable($productVideoThumb)) {
                $productVideoThumb = Utils::copyImageFromUrl($productVideoThumb, 'product');
            }

            Log::info('Brands Array', ['brands_array' => $brandsArr]);
            Log::info('Array Result', ['arr_result' => $lowercaseBrandsArr[$trimmedBrand] ?? null]);
            $pArr = [
                'image' => $productImageName,
                'video' => $productVideoName,
                'video_thumb' => $productVideoThumb,
                'warranty' => $row[20],
                'refundable' => $row[21],
                'slug' => $slug,
                'tags' => $row[33],
                'tax_rule_id' => $taxRulesArr[trim($row[34])],
                'brand_id' => trim($row[1]) == '' ? null : $lowercaseBrandsArr[$trimmedBrand],
                'shipping_rule_id' => $shippingRulesArr[trim($row[35])],
                'bundle_deal_id' => trim($row[37]) == '' ? null : $bundleDealsArr[trim($row[34])],
                'stock' => $row[9],
                'selling' => $row[19],
                'offered' => $row[10],
                'status' => $row[11],
                'sku' => $row[6],
                'barcode' => $row[7],
                'supplier_item_code' => $row[5],
                'banner_image' => $productBannerName,
                'admin_id' => $adminId
            ];

            if (trim($row[39])) {

                $updateArr = [];
                if ($lang) {

                    $updateArr = $pArr;
                } else {

                    $updateArr = array_merge($prodData, $pArr);
                }

                $existingProd = Product::where('id', trim($row[39]))->first();

                if ($existingProd) {
                    if (trim($row[4]) == '') {
                        unset($updateArr['slug']);
                    }

                    Product::where('id', trim($row[39]))->update($updateArr);

                    $prod = new Product();
                    $prod->id = trim($row[39]);

                } else {
                    $prod = Product::create(array_merge($prodData, $pArr));
                }


            } else {

                $prod = Product::create(array_merge($prodData, $pArr));

            }

            if ($lang) {
                $productLang = ProductLang::where('product_id', $prod->id)->first();
                $pLangArr = [
                    'product_id' => $prod->id,
                    'lang' => $lang,
                    'title' => $row[3],
                    'badge' => $row[33],
                    'unit' => $row[8],
                    'description' => $row[12],
                    'overview' => $row[13],
                    'meta_title' => $row[17],
                    'meta_description' => $row[18],
                ];

                if ($productLang) {
                    ProductLang::where('product_id', $prod->id)->update($pLangArr);

                } else {
                    ProductLang::create($pLangArr);
                }

                $pcs = explode(',', trim($row[27]));

                foreach ($pcs as $jk) {
                    if (trim($jk) == '') continue;

                    if (!key_exists(trim($jk), $productCollectionsArr)) {
                        $pc = ProductCollection::create([
                            'admin_id' => $adminId,
                            'title' => "",
                        ]);

                        ProductCollectionLang::create([
                            'product_collection_id' => $pc->id, 'title' => trim($jk), 'lang' => $lang
                        ]);

                        $productCollectionsArr[trim($jk)] = $pc->id;
                    }

                    $existingPc = CollectionWithProduct::where('product_collection_id', $productCollectionsArr[trim($jk)])
                        ->where('product_id', $prod->id)
                        ->first();

                    if (is_null($existingPc)) {
                        CollectionWithProduct::create([
                            'product_collection_id' => $productCollectionsArr[trim($jk)],
                            'product_id' => $prod->id
                        ]);
                    }
                }

            } else {

                $pcs = explode(',', trim($row[38]));

                foreach ($pcs as $jk) {

                    if (trim($jk) == '') continue;


                    if (!key_exists(trim($jk), $productCollectionsArr)) {


                        $pc = ProductCollection::create([
                            'admin_id' => $adminId,
                            'title' => trim($jk)
                        ]);


                        $productCollectionsArr[$pc->title] = $pc->id;
                    }

                    $existingPc = CollectionWithProduct::where('product_collection_id', $productCollectionsArr[trim($jk)])
                        ->where('product_id', $prod->id)
                        ->first();

                    if (is_null($existingPc)) {

                        CollectionWithProduct::create([
                            'product_collection_id' => $productCollectionsArr[trim($jk)],
                            'product_id' => $prod->id
                        ]);

                    }
                }
            }


            $categories = explode(',', $row[0]);

            foreach ($categories as $key => $c) {
                if (trim($c) == '') continue;

                if (!key_exists(trim($c), $categoriesArr)) {

                    if ($lang) {

                        $cat = Category::create([
                            'title' => "",
                            'admin_id' => $adminId
                        ]);

                        CategoryLang::create([
                            'category_id' => $cat->id,
                            'title' => trim($c),
                            'lang' => $lang
                        ]);

                    } else {
                        $cat = Category::create([
                            'title' => trim($c),
                            'admin_id' => $adminId
                        ]);

                    }

                    $categoriesArr[trim($c)] = $cat->id;
                }

                $primary = 0;
                if ($key == 0) {
                    $primary = 1;
                }

                $existingProductCat = ProductCategory::where('category_id', $categoriesArr[trim($c)])
                    ->where('product_id', $prod->id)
                    ->first();

                if (!$existingProductCat) {
                    ProductCategory::create([
                        'category_id' => $categoriesArr[trim($c)],
                        'product_id' => $prod->id,
                        'primary' => $primary
                    ]);
                }
            }


            try {
                // Read the fields directly from the row
                $sku = trim($row[6]);
                $quantity = trim($row[9]);
                $price = trim($row[10]) ?: trim($row[19]);
            
                // Check if an inventory record already exists for this product and SKU
                $existingInv = UpdatedInventory::where('product_id', $prod->id)
                    ->where('sku', $sku)
                    ->first();
            
                if ($existingInv) {
                    // Update the existing inventory
                    $existingInv->update([
                        'quantity' => $quantity,
                        'price' => $price
                    ]);
                } else {
                    // Create a new inventory
                    UpdatedInventory::create([
                        'product_id' => $prod->id,
                        'sku' => $sku,
                        'quantity' => $quantity,
                        'price' => $price
                    ]);
                }
            } catch (\Exception $ex) {
                throw new \Exception('Error in inventory row. ' . $ex->getMessage());
            }

            $images = [$row[22], $row[23], $row[24], $row[25], $row[26], $row[27], $row[28]];
            $images = array_filter($images);
            if ($images && count($images) > 0) {
                foreach ($images as $img) {
                    $imageName = trim($img);

                    if ($imageName == '') continue;

                    // Check if the image needs to be uploaded or searched in local storage
                    if (Utils::isUploadable($imageName)) {
                        $imageName = Utils::copyImageFromUrl($imageName, 'product');
                    } else {
                        // If the image is local, search for it in the public/uploads directory
                        \Log::info('imageName', ['image' => $imageName]);
                        $imageName = Utils::searchImageInStorage($imageName);
                    }
                    \Log::info('imageName', ['image' => $imageName]);
                    // Check if the image already exists in the database
                    if($imageName == null) continue;
                    $existingImg = ProductImage::where('image', $imageName)
                        ->where('product_id', $prod->id)
                        ->where('admin_id', $adminId)
                        ->first();

                    if (!$existingImg) {
                        ProductImage::create([
                            'image' => $imageName,
                            'product_id' => $prod->id,
                            'admin_id' => $adminId
                        ]);
                    }
                }
            }

        }
    }
}