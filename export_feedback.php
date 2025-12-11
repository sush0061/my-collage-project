<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "recommendation";

// Connect to database
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// SQL to join feedback with treatment_recommendations and calculate average rating
$sql = "
    SELECT 
        tr.recommendation AS recommendation_name,
        tr.recommendation_type,
        tr.age,
        tr.symptoms,
        tr.disease,
        IFNULL(AVG(f.rating), 0) AS average_rating,
        COUNT(f.id) AS total_feedbacks
    FROM treatment_recommendations tr
    LEFT JOIN feedback f
        ON f.recommendation_name = tr.recommendation
    GROUP BY 
        tr.recommendation,
        tr.recommendation_type,
        tr.age,
        tr.symptoms,
        tr.disease
    ORDER BY average_rating DESC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $filePath = __DIR__ . '/average_ratings.csv';
    $file = fopen($filePath, 'w');

    // Write CSV headers
    fputcsv($file, [
        'Recommendation Name',
        'Recommendation Type',
        'Age',
        'Symptoms',
        'Disease',
        'Average Rating',
        'Total Feedbacks'
    ]);

    // Write each row
    while ($row = $result->fetch_assoc()) {
        fputcsv($file, [
            $row['recommendation_name'],
            $row['recommendation_type'],
            $row['age'],
            $row['symptoms'],
            $row['disease'],
            round((float)$row['average_rating'], 2),
            $row['total_feedbacks']
        ]);
    }

    fclose($file);
    echo "✅ CSV file created successfully: $filePath";
} else {
    echo "⚠️ No feedback or recommendation data found.";
}

$conn->close();
?>
