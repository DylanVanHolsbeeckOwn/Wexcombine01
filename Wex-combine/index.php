<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Combineer WEX-bestanden</title>
</head>
<body>
    <h2>Combineer WEX-bestanden</h2>
    <form action="combine.php" method="post" enctype="multipart/form-data">
        <label for="file1">Selecteer het eerste WEX-bestand:</label><br>
        <input type="file" id="file1" name="file1" required><br><br>
        
        <label for="file2">Selecteer het tweede WEX-bestand:</label><br>
        <input type="file" id="file2" name="file2" required><br><br>

        <label for="xml">Selecteer het XML-bestand:</label><br>
        <input type="file" id="xml" name="xml"><br><br>

        <label for="filename">Enter Filename:</label>
        <input type="text" name="filename" id="filename"><br><br>

        <button type="submit" name="submit">Combineer en Converteer</button>
    </form>

    <?php
    function displayDownloadLink($filename, $filetype) {
        if (file_exists($filename)) {
            echo '<br><br><a href="' . $filename . '" download="' . basename($filename) . '"><button>Download het ' . $filetype . '-bestand</button></a>';
        }
    }

    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';

    if ($filename) {
        displayDownloadLink($filename . '.xlsx', 'Excel');
        displayDownloadLink($filename . '.wex', 'WEX');
    }
    ?>

</body>
</html>

