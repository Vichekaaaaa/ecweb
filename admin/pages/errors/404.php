<?php
// NotFoundView class to handle rendering of the 404 page
class NotFoundView {
    public function render() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 Not Found</title>
            <style>
                body {
                    text-align: center;
                    font-family: Arial, sans-serif;
                    margin-top: 50px;
                }
                h1 {
                    color: red;
                }
            </style>
        </head>
        <body>
            <h1>404 - Page Not Found</h1>
            <p>Sorry, the page you are looking for does not exist.</p>
            <a href="index.php">Go Back to Dashboard</a>
        </body>
        </html>
        <?php
    }
}

// Main execution
$notFoundView = new NotFoundView();
$notFoundView->render();