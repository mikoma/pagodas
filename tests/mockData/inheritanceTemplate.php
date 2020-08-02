<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $templateData['title']; ?></title>
</head>
<body>
    <p>
        text<br>
        more text<br>
        even more text<br>
    </p>
    <main>
        <h2>This is a default child template with a '<?php echo $templateData['variable']; ?>' and another child:</h2>
        this template extends default
    </main>
</body>
</html>