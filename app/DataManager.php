<?php

namespace App;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class DataManager
{
    private string $productFile;

    public function __construct($productFile = 'data/products.json')
    {
        $this->productFile = $productFile;
    }

    public function loadProducts(): ?array
    {
        $productsData = json_decode(file_get_contents($this->productFile));
        if ($productsData === null) {
            return null;
        }

        $products = [];
        foreach ($productsData as $productData) {
            $products[] = new Product(
                $productData->id,
                $productData->name,
                $productData->description,
                $productData->amount,
                $productData->createdBy,
                $productData->createdAt,
                $productData->updatedAt,
                $productData->deletedAt
            );
        }
        return $products;
    }

    public function saveProducts(array $products): void
    {
        $productsData = [];
        foreach ($products as $product) {
            $productsData[] = $product->jsonSerialize();
        }
        file_put_contents($this->productFile, json_encode($productsData, JSON_PRETTY_PRINT));
    }

    public function displayProducts(): void
    {
        $products = $this->loadProducts();

        if (empty($products)) {
            echo "No products available.\n";
            return;
        }

        $outputTasks = new ConsoleOutput();
        $tableProducts = new Table($outputTasks);
        $tableProducts
            ->setHeaders(['Index', 'Name', 'Description', 'Amount', 'Created By', 'Created At', 'Updated At'])
            ->setRows(array_map(function (Product $product): array {
                return [
                    $product->getId(),
                    $product->getName(),
                    $product->getDescription(),
                    $product->getAmount(),
                    $product->getCreatedBy(),
                    $product->getCreatedAt()->toIso8601String(),
                    $product->getUpdatedAt() ? $product->getUpdatedAt()->toIso8601String() : 'N/A',
                ];
            }, $products));
        $tableProducts->render();
    }
}