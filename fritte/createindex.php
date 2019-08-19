<?php
    // Create Index Aufgaben:
    // + HTML-Dateien in den vordefinierten Ordnern finden 
    // + Für die Verzeichnisse eine Dateiliste anlegen
    // - Aus der Dateilist einen HTML-Menübaum erstellen
    // + Dateiliste Ordnerweise im Cache ablegen
    // + md Dateien in HTML umwandeln
    //   Dabei HTML Menues erstellen. Aus den Dateiliste im Cache
    // - HTML Menus gesondert erstellen, sodass eine manuelle
    //   Sortierung möglich ist

    require_once 'inc/libs/fritte.class.php';
    require_once 'inc/libs/fritte.template.class.php';

    // Create a new Fritte.Template instance
    // You can also use Fritte only, if you don't need the 
    // template stuff
    // $fritte = new Fritte 
    
    $fritte = new Template;
    
    // Load the default config-file
    // Also called at construction
    // Optional: Pass a different path
    // $fritte->loadConfig();

    // Set the content directorys which Fritte must scan
    // and are defined in the config.ini
    // Also called at construction
    // Optional: Define additional folders manually (array is needed)
    // $fritte->setContentDirs();

    // Set the fileextensions Fritte should look for
    // Ignores every other fileextension if set, except *.meta
    
    //$fritte->setFileExtensions(array("md"));
    $fritte->fileExtensions = array("md");

    // If you want to build the template from different parts, you 
    // can do that. But it is optional
    //$fritte->addTemplatePart("head.html");
    //$fritte->addTemplatePart("body.html");
    //$fritte->addTemplatePart("footer.html");
    //$fritte->addTemplatePart("overview.html");

    // Setup placeholders for the template
    $fritte->setPlaceholder('title', "");
    $fritte->setPlaceholder('description', "");
    $fritte->setPlaceholder('keywords', "");
    
    // Crawls every content folder
    foreach ($fritte->dirs as $folder)
    {   
        $fritte->fileExtensions = array("md");

        $currentdir = ".".DIRECTORY_SEPARATOR.$fritte->config['paths']['content'].DIRECTORY_SEPARATOR.$folder;
        $files = $fritte->getDirContent($currentdir);

        $fritte->writeFileIndex($folder, $files);
        $fritte->createHTML($fritte->getFileIndex($folder));

        // get every html file
        // and build the template around it
        // or something like that
        $fritte->fileExtensions = array("html");
        $files = $fritte->getDirContent($currentdir);
        
        echo "Building file with template :".$files['files'][0]."</br>";
        $fritte->buildTemplate($files['files']);
    }
?>