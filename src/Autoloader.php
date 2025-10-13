<?php
namespace BV;

//Transforma namespace em caminho de arquivos, e caso passe pela regra (o namespace é do plugin "BV"), 
// da require sempre que uma classe desconhecida é encontrada, isso evita que eu tenha que ficar dando require_once em todos os arquivos
// porém o nome dos arquivos devem seguir o padrão, da classe, caso contrário o autoloader não vai encontrar o arquivo.

final class Autoloader {
    public static function init(string $prefix, string $baseDir): void {
        spl_autoload_register(function ($class) use ($prefix, $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relative = substr($class, $len + 1);
            $file = $baseDir . '/' . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) require $file;
        });
    }
}
