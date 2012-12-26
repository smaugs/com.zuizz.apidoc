<?php



$farr    = explode(".", $this->values['identifier']);
$version = $farr[0];

$feature = implode(".", array($farr[1], $farr[2], $farr[3]));

$varr = array_slice($farr, 4);

$targetarr = $varr;

if ($version == 0) {
    $varr[]      = "doc.json";
    $targetarr[] = "index.{$this->values['language']}";
} else {
    $varr[]      = "{$version}.doc.json";
    $targetarr[] = "{$version}.index.{$this->values['language']}";
}


$view = implode("/", $varr);
$file = ZU_DIR_FEATURE . implode("/", array($feature, "rest", $view));
$doc  = json_decode(file_get_contents($file));


$targetfile = ZU_DIR_FEATURE . implode("/", array($feature, "rest", implode("/", $targetarr)));

if (is_file($targetfile)) {
    ZU::header(400);
    echo "The sourcefile [{$targetfile}] already exists";
    die();
} else {


// Init
    $methods = array("GET", "HEAD", "PUT", "DELETE", "POST", "OPTIONS", "TRACE", "CONNECT");
    $types   = array("numeric", "string", "int", "float", "email", "regularexpression", "url", "unsafe", "ip adress", "Boolean");


    // string aufbauen
    $sourcecode = "<?php \n";


    // description
    $sourcecode .= "/* \n";
    $sourcecode .= " * " . $doc->title . "\n";
    foreach (preg_split("/(\r?\n)/", $doc->description) as $line) {
        // do stuff with $line
        $sourcecode .= " * " . $line . "\n";
    }
    $sourcecode .= " *\n";
    $sourcecode .= " *\n";
    $sourcecode .= " * @author \n";
    $sourcecode .= " * @package " . $feature . "\n";
    $sourcecode .= " * @subpackage " . implode(".", $varr) . " \n";
    $sourcecode .= " *\n";
    $sourcecode .= " *\n";
    $sourcecode .= " *\n";

    // Permissions
    $sourcecode .= " * Permissions / Roles \n";
    foreach ($doc->permission as $tmp) {

        $sourcecode .= " * " . $tmp->role . " => " . $tmp->description . "\n";

        // Noch nicht existierende Rollen eintragen
        $count = ZU::count('org_functional_role', array('title'=> $tmp->role));
        if ($count == 0) {
            echo $tmp->role;
            $role              = ORM::for_table('org_functional_role')->create();
            $role->title       = $tmp->role;
            $role->description = "doc2code autogenerated role";
            $role->c_date      = ZU_NOW;
            $role->save();
        }

    }
    $sourcecode .= " *\n";
    $sourcecode .= " *\n";
    $sourcecode .= " *\n";


    // states
    $sourcecode .= " * States \n";
    $sourcecode .= " *\n";

    foreach ($doc->states as $tmp) {
        $sourcecode .= " * State " . $tmp->code . "  => " . $tmp->message . "\n";
        foreach (preg_split("/(\r?\n)/", $tmp->description) as $line) {
            $sourcecode .= " * " . $line . "\n";
        }
        $sourcecode .= " *\n";
    }
    $sourcecode .= " *\n";
    $sourcecode .= " *\n";

    // Variablen
    $sourcecode .= " * Available variables \n";
    $sourcecode .= " *\n";
    foreach ($doc->parameter as $tmp) {
        $sourcecode .= " * " . $tmp->description . "\n";
        $sourcecode .= " * varname:" . $tmp->name . " (" . $types[$tmp->type] . "), always available:" . $tmp->required . "\n";
        $sourcecode .= " *\n";
    }
    $sourcecode .= " *\n";
    $sourcecode .= " *\n";

    $sourcecode .= " */\n\n";

    $sourcecode .= "// your code somewhere here \n\n";
    foreach ($doc->parameter as $tmp) {
        $sourcecode .= "\$this->values['" . $tmp->name . "'];\n";
    }
    $sourcecode .= "\n\n";

    $sourcecode .= "\$this->data['message'] = \"dont forget the message\";\n";

    $sourcecode .= "\n\n";


    // Mimetypes
    foreach ($doc->mimetype as $tmp) {
        $sourcecode .= "/* \n * Mimetype " . $tmp->name . " \n";
        $sourcecode .= " * Returns:\n";
        foreach (preg_split("/(\r?\n)/", $tmp->response) as $line) {
            // do stuff with $line
            $sourcecode .= " * " . $line . "\n";
        }
        $sourcecode .= "*/\n\n";
    }


    foreach ($doc->mimetype as $tmp) {
        if ($tmp->is_default == 1) {
            $sourcecode .= "// set default mimetype\n";
            $sourcecode .= "if (!\$this->mimetype) {\n";
            $sourcecode .= "    \$this->mimetype = '" . $tmp->name . "'; \n";
            $sourcecode .= "}\n\n";
            break;
        }
    }
    $sourcecode .= "switch (\$this->mimetype) {\n";
    $sourcecode .= "   case \"json\":\n";
    $sourcecode .= "     header('Content-type: application/json');\n";
    $sourcecode .= "     \$this->contentbuffer = json_encode(\$this->data);\n";
    $sourcecode .= "   break;\n";
    $sourcecode .= "   case \"xml\":\n";
    $sourcecode .= "     header('Content-type: application/xml');\n";
    $sourcecode .= "     ZU::load_class('lalit.array2xml', 'xml', true);\n";
    $sourcecode .= "     \$xml = Array2XML::createXML('auth', \$this->data);\n";
    $sourcecode .= "     \$this->contentbuffer = \$xml->saveXML();\n";
    $sourcecode .= "   break;\n";
    $sourcecode .= "}";

    //

    // file schreiben
    file_put_contents($targetfile, $sourcecode);
    chmod($targetfile, 0777);
    ZU::header(201);


    $this->data['message'] = "dont forget the message";


    /*
     * Mimetype json
     * Returns:
     *
    */

    switch ($this->mimetype) {
        case "json":
            header('Content-type: application/json');
            $this->contentbuffer = json_encode($this->data);
            break;

    }
}


function draw_modules($modules, $methods)
{

    line();
    echo "| ";
    echo   str_pad("#", floor($GLOBALS['zeilenbreite'] * 0.07), " ") . " | ";
    echo   str_pad("Title", floor($GLOBALS['zeilenbreite'] * 0.4), " ") . " | ";
    echo   str_pad("Request", floor($GLOBALS['zeilenbreite'] * 0.45), " ") . " | ";
    echo   str_pad("Method", floor($GLOBALS['zeilenbreite'] * 0.08), " ") . " | \n";

    line();


    foreach ($modules as $index => $mod) {
        echo "| ";
        echo   str_pad($index, floor($GLOBALS['zeilenbreite'] * 0.07), " ") . " | ";
        echo   str_pad($mod['title'], floor($GLOBALS['zeilenbreite'] * 0.4), " ") . " | ";
        echo   str_pad($mod['request'], floor($GLOBALS['zeilenbreite'] * 0.45), " ") . " | ";
        echo   str_pad($methods[$mod['method']], floor($GLOBALS['zeilenbreite'] * 0.08), " ") . " | \n";
    }
    line();


}

function line()
{
    echo "| ";
    echo   str_pad("", floor($GLOBALS['zeilenbreite'] * 0.07), "-") . " | ";
    echo   str_pad("", floor($GLOBALS['zeilenbreite'] * 0.4), "-") . " | ";
    echo   str_pad("", floor($GLOBALS['zeilenbreite'] * 0.45), "-") . " | ";
    echo   str_pad("", floor($GLOBALS['zeilenbreite'] * 0.08), "-") . " | \n";

}