<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Brand;

class UpdateProductBrands extends Command
{
    protected $signature = 'products:update-brands';
    protected $description = 'Replace private brand IDs with public ones';

    public function handle()
    {
        try {
            DB::transaction(function () {
                $updatedCount = 0;
                $publicBrands = Brand::where('status', 'public')
                    ->get()
                    ->keyBy(function ($brand) {
                        return strtolower($brand->title);
                    });
        
                Brand::where('status', 'private')
                    ->chunk(100, function ($privateBrands) use (&$updatedCount, $publicBrands) {
                        foreach ($privateBrands as $privateBrand) {
                            $publicBrand = $publicBrands->get(strtolower($privateBrand->title));
                            
                            if ($publicBrand) {
                                $updated = Product::where('brand_id', $privateBrand->id)
                                                  ->update(['brand_id' => $publicBrand->id]);
                                $updatedCount += $updated;
                                $this->info("Updated {$updated} products from Brand ID {$privateBrand->id} to {$publicBrand->id}");
                            }
                        }
                    });
        
                $this->info("Total Products Updated: {$updatedCount}");
        
                // Delete private brands that have no associated products
                $deletedCount = Brand::where('status', 'private')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('products')
                              ->whereRaw('products.brand_id = brands.id');
                    })
                    ->delete();
        
                $this->info("Deleted {$deletedCount} private brands with no associated products");
            });
        } catch (\Exception $e) {
            $this->error("An error occurred during brand update: " . $e->getMessage());
            // Log the error or perform any other cleanup/rollback if necessary
        }
    }
}
    
// In the above code, we have created a new command  products:update-brands  that will replace the private brand IDs with public ones. 
// To run this command, execute the following command in the terminal: 
// php artisan products:update-brands

// This command will update the products with private brand IDs to public brand IDs. 
// Conclusion 
// In this article, we have learned how to create custom Artisan commands in Laravel. We have also seen how to pass arguments and options to the commands. 
// You can create custom commands for various tasks like updating the database, sending emails, and more. 
// I hope this article helps you to understand how to create custom Artisan commands in Laravel. 
// Save my name, email, and website in this browser for the next time I comment.