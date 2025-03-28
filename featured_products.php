<?php
// Include database connection
include('connection.php');

// Function to get featured products
function getFeaturedProducts($limit = 12) {
    global $conn;
    
    // Check if the category field in products table is an integer (using categories table)
    $check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
    $is_category_int = false;
    
    if ($check_column->num_rows > 0) {
        $column_info = $check_column->fetch_assoc();
        $is_category_int = (strpos(strtolower($column_info['Type']), 'int') !== false);
    }
    
    // Get all available categories from the categories table
    $all_categories = [];
    $categories_query = "SELECT category_id, category_name FROM categories ORDER BY category_name";
    $categories_result = $conn->query($categories_query);
    
    if ($categories_result && $categories_result->num_rows > 0) {
        while ($row = $categories_result->fetch_assoc()) {
            $all_categories[$row['category_id']] = [
                'id' => $row['category_id'],
                'name' => $row['category_name']
            ];
        }
    }
    
    // Define the categories we want to ensure are included
    $main_categories = ['accentchair', 'bedframe', 'coffeetables', 'cornerdesk', 
                      'desk', 'dresser', 'officechair', 'rectangle', 
                      'round', 'sidetable', 'sofas', 'square'];
    
    // Prepare the SQL query based on the category field type
    if ($is_category_int) {
        // If category is integer (using categories table)
        $sql = "SELECT p.*, c.category_name, c.category_id
                FROM products p
                LEFT JOIN categories c ON p.category = c.category_id
                WHERE p.product_quantity > 0
                ORDER BY RAND()
                LIMIT ?";
                
        // Prepare and execute the query
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Convert to array with category information
        $featured_products = [];
        while ($product = $result->fetch_assoc()) {
            // Add category information
            $product['category_info'] = [
                'id' => $product['category_id'],
                'name' => $product['category_name'],
                'slug' => strtolower(trim($product['category_name']))
            ];
            
            $featured_products[] = $product;
        }
        
        // Return as a result set
        return createResultSet($featured_products);
    } else {
        // For string categories, we'll still try to match with our main categories
        $featured_products = [];
        
        // Try to get at least one product from each main category
        foreach ($main_categories as $category) {
            $cat_sql = "SELECT * FROM products WHERE category = ? AND product_quantity > 0 LIMIT 1";
            $cat_stmt = $conn->prepare($cat_sql);
            $cat_stmt->bind_param("s", $category);
            $cat_stmt->execute();
            $result = $cat_stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $product = $result->fetch_assoc();
                
                // Add category information
                $product['category_info'] = [
                    'id' => null,
                    'name' => ucfirst($category),
                    'slug' => $category
                ];
                
                $featured_products[] = $product;
            }
        }
        
        // Calculate how many more products we need to reach the limit
        $remaining = $limit - count($featured_products);
        
        // If we need more products, get them randomly from any category
        if ($remaining > 0) {
            // Get product IDs we already have to avoid duplicates
            $existing_ids = [];
            foreach ($featured_products as $product) {
                $existing_ids[] = $product['product_id'];
            }
            
            $additional_sql = "SELECT * FROM products WHERE product_quantity > 0";
            
            // Exclude products we already have
            if (!empty($existing_ids)) {
                $id_placeholders = implode(',', array_fill(0, count($existing_ids), '?'));
                $additional_sql .= " AND product_id NOT IN ($id_placeholders)";
            }
            
            $additional_sql .= " ORDER BY RAND() LIMIT $remaining";
            
            // Standard query if we don't have existing products
            if (empty($existing_ids)) {
                $add_stmt = $conn->prepare($additional_sql);
            } else {
                $add_stmt = $conn->prepare($additional_sql);
                $add_types = str_repeat('i', count($existing_ids));
                $add_stmt->bind_param($add_types, ...$existing_ids);
            }
            
            $add_stmt->execute();
            $additional_result = $add_stmt->get_result();
            
            // Add additional products to our featured list
            while ($product = $additional_result->fetch_assoc()) {
                // Add category information
                $category = isset($product['category']) ? $product['category'] : '';
                $product['category_info'] = [
                    'id' => null,
                    'name' => ucfirst($category),
                    'slug' => strtolower(trim($category))
                ];
                
                $featured_products[] = $product;
            }
        }
        
        // Return the collection as a result set
        return createResultSet($featured_products);
    }
}

// Helper function to convert array to a result set
function createResultSet($array) {
    // Create a custom result set from array
    return new class($array) {
        private $data;
        private $position = 0;
        
        public function __construct($data) {
            $this->data = $data;
        }
        
        public function fetch_assoc() {
            if ($this->position >= count($this->data)) {
                return null;
            }
            return $this->data[$this->position++];
        }
        
        public function __get($name) {
            if ($name === 'num_rows') {
                return count($this->data);
            }
            return null;
        }
    };
}

// Get data
$featured_products = getFeaturedProducts();
?> 