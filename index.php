<?php

require_once 'vendor/autoload.php';

use App\DataManager;
use App\Product;
use Carbon\Carbon;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

function getUsers(): array
{
    $json = file_get_contents('data/users.json');
    return json_decode($json)->users;
}

function findObjectByAccessCode(string $accessCode): ?stdClass
{
    $users = getUsers();
    $filtered = array_filter($users, function (stdClass $user) use ($accessCode): bool {
        return $user->accessCode === $accessCode;
    });
    return reset($filtered) ?? null;
}

function createLogEntry(string $entry): void
{
    file_put_contents(
        'logs/warehouse.log',
        Carbon::now()->toIso8601String() . " " . $entry . PHP_EOL,
        FILE_APPEND
    );
}

function addProducts(
    DataManager $dataManager,
    string      $name,
    string      $description,
    int         $amount,
    string      $createdBy): void
{
    $products = $dataManager->loadProducts();
    $id = count($products) + 1;
    $newProduct = new Product($id, $name, $description, $amount, $createdBy);
    $products[] = $newProduct;
    $dataManager->saveProducts($products);
    createLogEntry("Product added: $name by $createdBy");
}

function updateProductAmount(DataManager $dataManager, int $id, int $amount): void
{
    $products = $dataManager->loadProducts();
    foreach ($products as $product) {
        if ($product->getId() === $id) {
            $product->setAmount($amount);
            $dataManager->saveProducts($products);
            createLogEntry("Product updated: ID $id, amount changed by $amount units");
            return;
        }
    }
    echo "Product not found\n";
}

function deleteProduct(DataManager $dataManager, int $id): void
{
    $products = $dataManager->loadProducts();
    $deletedProduct = null;

    foreach ($products as $product) {
        if ($product->getId() === $id) {
            $product->setDeletedAt(Carbon::now());
            $deletedProduct = $product;
            break;
        }
    }

    if ($deletedProduct !== null) {
        $dataManager->saveProducts($products);
        createLogEntry("Product deleted: ID $id");
        echo "Product deleted successfully.\n";
    } else {
        echo "Product not found.\n";
    }
}


echo "Welcome to the WareHouse app!\n";
$accessCode = (string)readline("Enter your access code: ");

if (strlen($accessCode) !== 4) {
    exit("Invalid access code. Please try again.\n");
}
$user = findObjectByAccessCode($accessCode);
if ($user === null) {
    exit("No user found.");
}
echo "Welcome $user->username!\n";

$dataManager = new DataManager();

while (true) {
    $outputTasks = new ConsoleOutput();
    $tableActivities = new Table($outputTasks);
    $tableActivities
        ->setHeaders(['Index', 'Action'])
        ->setRows([
            ['1', 'Create'],
            ['2', 'Change amount'],
            ['3', 'Delete'],
            ['4', 'Display'],
            ['0', 'Exit'],
        ])
        ->render();
    $action = (int)readline("Enter the index of the action: ");

    if ($action === 0) {
        break;
    }

    switch ($action) {
        case 1:
            $productName = (string)readline("Enter the name of product: ");
            $productDescription = (string)readline("Enter the description: ");
            $productAmount = (int)readline("Enter the amount: ");
            addProducts($dataManager, $productName, $productDescription, $productAmount, $user->username);
            break;
        case 2:
            try {
                $dataManager->displayProducts();
            } catch (Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                break;
            }
            $id = (int)readline("Enter product ID: ");
            $productAmount = (int)readline("Enter the number of units you want add/(-)remove: ");
            updateProductAmount($dataManager, $id, $productAmount);
            break;
        case 3:
            try {
                $dataManager->displayProducts();
            } catch (Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                break;
            }
            $id = (int)readline("Enter product ID to delete: ");
            deleteProduct($dataManager, $id);
            break;
        case 4:
            try {
                $dataManager->displayProducts();
            } catch (Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
            break;
        default:
            echo "Invalid action. Please try again.\n";
            break;
    }
}



