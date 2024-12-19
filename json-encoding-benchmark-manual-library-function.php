<?php

// Benchmarking helper function
function benchmark($name, $func, $data, $iterations = 1000)
{
    // Warm up the function
    $func($data);

    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    for ($i = 0; $i < $iterations; $i++) {
        $func($data);
    }

    $timeElapsed = (microtime(true) - $startTime) * 1000 / $iterations;
    $memoryUsed = (memory_get_usage() - $startMemory) / $iterations;

    return [
        'name' => $name,
        'time_ms' => round($timeElapsed, 3),
        'memory_bytes' => round($memoryUsed, 2),
    ];
}

// The original legacy implementation
function jsonEncodeUTFnormalWpf($value)
{
    if (is_int($value)) {
        return (string)$value;
    } elseif (is_string($value)) {
        $value = str_replace(array('\\', '/', '"', "\r", "\n", "\b", "\f", "\t"),
            array('\\\\', '\/', '\"', '\r', '\n', '\b', '\f', '\t'), $value);
        $convmap = array(0x80, 0xFFFF, 0, 0xFFFF);
        $result = '';
        for ($i = strlen($value) - 1; $i >= 0; $i--) {
            $mb_char = substr($value, $i, 1);
            $result = $mb_char . $result;
        }
        return '"' . $result . '"';
    } elseif (is_float($value)) {
        return str_replace(',', '.', $value);
    } elseif (is_null($value)) {
        return 'null';
    } elseif (is_bool($value)) {
        return $value ? 'true' : 'false';
    } elseif (is_array($value)) {
        $with_keys = false;
        $n = count($value);
        for ($i = 0, reset($value); $i < $n; $i++, next($value)) {
            if (key($value) !== $i) {
                $with_keys = true;
                break;
            }
        }
    } elseif (is_object($value)) {
        $with_keys = true;
    } else {
        return '';
    }
    $result = array();
    if ($with_keys) {
        foreach ($value as $key => $v) {
            $result[] = jsonEncodeUTFnormalWpf((string)$key) . ':' . jsonEncodeUTFnormalWpf($v);
        }
        return '{' . implode(',', $result) . '}';
    } else {
        foreach ($value as $key => $v) {
            $result[] = jsonEncodeUTFnormalWpf($v);
        }
        return '[' . implode(',', $result) . ']';
    }
}

// Modern implementation
function jsonEncodeUTFnormalWpf_modern($value)
{
    return json_encode($value,
        JSON_UNESCAPED_UNICODE |
        JSON_PARTIAL_OUTPUT_ON_ERROR |
        JSON_INVALID_UTF8_SUBSTITUTE
    ) ?: '';
}

function generateRealisticEcommerceData($numCategories = 100)
{
    $data = ['taxonomies' => []];

    // Common product attributes
    $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '2XL', '3XL', '4XL'];
    $colors = ['Red', 'Blue', 'Green', 'Black', 'White', 'Navy', 'Grey', 'Brown', 'Purple', 'Yellow', 'Orange', 'Pink'];
    $materials = ['Cotton', 'Polyester', 'Wool', 'Linen', 'Silk', 'Denim', 'Leather', 'Canvas', 'Nylon'];
    $brands = ['Nike', 'Adidas', 'Puma', 'Under Armour', 'New Balance', 'Reebok', 'ASICS', 'Fila', 'Champion'];

    // Generate main product categories with nested subcategories
    $mainCategories = [
        'Men' => ['T-Shirts', 'Jeans', 'Jackets', 'Shoes', 'Accessories', 'Sportswear', 'Formal Wear', 'Underwear'],
        'Women' => ['Dresses', 'Tops', 'Pants', 'Skirts', 'Shoes', 'Accessories', 'Sportswear', 'Lingerie'],
        'Kids' => ['Boys', 'Girls', 'Babies', 'School Wear', 'Shoes', 'Accessories'],
        'Home' => ['Bedding', 'Bath', 'Kitchen', 'Decor', 'Furniture', 'Storage'],
        'Electronics' => ['Phones', 'Tablets', 'Laptops', 'Accessories', 'Gaming'],
        'Sports' => ['Running', 'Training', 'Swimming', 'Yoga', 'Team Sports', 'Outdoor']
    ];

    foreach ($mainCategories as $main => $subs) {
        $category = [
            'name' => $main,
            'slug' => strtolower($main),
            'description' => "Main category for $main products",
            'count' => rand(1000, 5000),
            'subcategories' => []
        ];

        foreach ($subs as $sub) {
            $subCategory = [
                'name' => $sub,
                'slug' => strtolower(str_replace(' ', '-', $sub)),
                'description' => "Subcategory for $sub under $main",
                'count' => rand(100, 1000),
                'attributes' => []
            ];

            // Add relevant attributes based on category
            if (in_array($sub, ['T-Shirts', 'Tops', 'Dresses'])) {
                $subCategory['attributes'] = [
                    'size' => array_combine($sizes, array_map(function () {
                        return rand(10, 100);
                    }, $sizes)),
                    'color' => array_combine($colors, array_map(function () {
                        return rand(5, 50);
                    }, $colors)),
                    'material' => array_combine($materials, array_map(function () {
                        return rand(20, 80);
                    }, $materials)),
                    'brand' => array_combine($brands, array_map(function () {
                        return rand(30, 150);
                    }, $brands))
                ];
            }

            $category['subcategories'][] = $subCategory;
        }

        $data['taxonomies'][] = $category;
    }

    // Add common filters that span categories
    $data['global_filters'] = [
        'price_ranges' => [
            '0-25' => rand(500, 1000),
            '25-50' => rand(1000, 2000),
            '50-100' => rand(800, 1500),
            '100-200' => rand(500, 1000),
            '200+' => rand(200, 500)
        ],
        'ratings' => [
            '5' => rand(1000, 2000),
            '4' => rand(2000, 3000),
            '3' => rand(1000, 2000),
            '2' => rand(500, 1000),
            '1' => rand(100, 500)
        ],
        'on_sale' => rand(1000, 3000),
        'in_stock' => rand(5000, 10000)
    ];

    return $data;
}

$testData['ecommerce'] = generateRealisticEcommerceData();


// Run benchmarks
foreach ($testData as $case => $data) {
    echo "\nTesting $case data structure:\n";
    $results = [
        benchmark('Legacy Implementation', 'jsonEncodeUTFnormalWpf', $data),
        benchmark('Modern Implementation', 'jsonEncodeUTFnormalWpf_modern', $data)
    ];

    foreach ($results as $result) {
        printf(
            "%s:\n  Time: %.3f ms\n  Memory: %.2f bytes\n",
            $result['name'],
            $result['time_ms'],
            $result['memory_bytes']
        );
    }
}
