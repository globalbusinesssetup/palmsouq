<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\UpdatedInventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductsUpdate implements ToCollection
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
        $adminId = Auth::user()->id;
        // Skip the first row (header/title row)
        $data = $rows->skip(1);

        foreach ($data as $row) {
            $sku = trim($row[0]);
            $supplier_code = trim($row[1]);
            $supplier = trim($row[2]);
            $stock = trim($row[3]);
            $sellingPrice = trim($row[4]);
            $offerPrice = trim($row[5]);

            // Find the product by SKU
            $product = Product::where('sku', $sku)->first();

            if ($product) {
                // Keep old values if new values are empty
                $sellingPrice = $sellingPrice ?: $product->selling;
                $offerPrice = $offerPrice ?: $product->offered;

                // Update the stock, selling price, and offer price
                $product->update([
                    'stock' => $stock,
                    'supplier_item_code' => $supplier_code,
                    'supplier' => $supplier,
                    'selling' => $sellingPrice,
                    'offered' => $offerPrice,
                ]);

                // Update the inventory if it exists
                $existingInv = UpdatedInventory::where('product_id', $product->id)
                    ->where('sku', $sku)
                    ->first();

                if ($existingInv) {
                    $existingInv->update([
                        'quantity' => $stock,
                        'price' => $offerPrice ?: $sellingPrice,
                    ]);
                } else {
                    // Create a new inventory record if it doesn't exist
                    UpdatedInventory::create([
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'quantity' => $stock,
                        'price' => $offerPrice ?: $sellingPrice,
                    ]);
                }
            }
        }
    }
}
