<?php

namespace App;

use \Core\Validator;

class Command
{
    const APP_NAME = 'Apartment cleaning service admin';
    const APP_VER = '1.0';
    const FILENAME = 'data.json';

    public static function execute(array $params): void
    {
        echo self::APP_NAME.' v'.self::APP_VER."\n";

        if (!isset($params['command']) || $params['command'] === __FUNCTION__ || !method_exists(self::class, $params['command'])) {
            exit(
"Unknown command given on command line.
Available options:
    --command insert --name \"your full name\" --email \"email\" --phone \"phone numer\" --address \"apartment address\" --date \"date and time e.g. ".(new \DateTime('now'))->format('Y-m-d H:i')."\"
    --command update --id \"record id\" [--name \"your full name\"] [--email \"email\"] [--phone \"phone numer\"] [--address \"apartment address\"] [--date \"date and time\"]
    --command delete --id \"record id\"
    --command list --from \"date\" --to \"date\"
    --command import --file \"file name.csv\"
    --command export --file \"file name.csv\"

Anything shown enclosed within square brackets is optional. The square brackets themselves are not typed.
A vertical bar that separates two or more elements indicates that any one of the elements can be typed.
"
            );
        }

        $method = $params['command'];
        unset($params['command']);
        if (self::$method($params) !== false) {
            echo "Done.\n";
        }
    }

    private static function insert(array $params): int|bool
    {
        Validator::validate($params, [
            'name' => ['required' => null, 'string' => ['min' => 4, 'max' => 50]],
            'email' => ['required' => null, 'string' => ['max' => 64], 'email' => null],
            'phone' => ['required' => null, 'string' => ['min' => 9], 'contain_digit' => ['min' => 9, 'max' => 11]],
            'address' => ['required' => null, 'string' => ['min' => 15, 'max' => 80], 'contain_alpha' => ['min' => 8], 'contain_digit' => ['min' => 1]],
            'date' => ['required' => null, 'datetime' => ['min' => 'now', 'max' => '+1 years']]
        ]);

        if (!is_file(self::FILENAME)) {
            file_put_contents(self::FILENAME, json_encode([], JSON_THROW_ON_ERROR));
        }

        $data = self::getData();
        $id = time();
        $data[$id] = $params;
        echo "New record ID: $id\n";
        return self::putData($data);
    }

    private static function update(array $params): int|bool
    {
        Validator::validate($params, [
            'id' => ['required' => null, 'string' => ['min' => 10], 'digit' => null],
            'name' => ['nullable' => null, 'string' => ['min' => 4, 'max' => 50]],
            'email' => ['nullable' => null, 'string' => ['max' => 64], 'email' => null],
            'phone' => ['nullable' => null, 'string' => ['min' => 9], 'contain_digit' => ['min' => 9, 'max' => 11]],
            'address' => ['nullable' => null, 'string' => ['min' => 15, 'max' => 80], 'contain_alpha' => ['min' => 8], 'contain_digit' => ['min' => 1]],
            'date' => ['nullable' => null, 'datetime' => ['min' => 'now', 'max' => '+1 years']]
        ]);

        $data = self::getData();

        if (!isset($data[$params['id']])) {
            echo "No record found with ID $params[id]\n";
            return false;
        }

        foreach ($params as $key => $value) {
            if ($key != 'id') {
                $data[$params['id']][$key] = $value;
            }
        }

        return self::putData($data);
    }

    private static function delete(array $params): int|bool
    {
        Validator::validate($params, [
            'id' => ['required' => null, 'string' => ['min' => 10], 'digit' => null]
        ]);

        $data = self::getData();

        if (!isset($data[$params['id']])) {
            echo "No record found with ID $params[id]\n";
            return false;
        }

        unset($data[$params['id']]);
        return self::putData($data);
    }

    private static function list(array $params): void
    {
        Validator::validate($params, [
            'from' => ['required' => null, 'date' => ['min' => '-50 years', 'max' => '+50 years']],
            'to' => ['required' => null, 'date' => ['min' => '-50 years', 'max' => '+50 years']]
        ]);

        $from = strtotime($params['from']);
        $to = strtotime($params['to']);

        $data = self::getData();
        $result = array_filter($data, function($value) use ($from, $to): bool {
            if (isset($value['date'])) {
                $v = strtotime($value['date']);
                if ($from < $to) {
                    return ($v >= $from && $v <= $to);
                }
                return ($v >= $to && $v <= $from);
            }
            return false;
        });

        uasort($result, function(array $a, array $b) use ($from, $to): int {
            if ($from < $to) {
                return strtotime($a['date']) <=> strtotime($b['date']);
            }
            return strtotime($b['date']) <=> strtotime($a['date']);
        });

        $colWidths = [14, 30, 35, 20, 45, 22];
        $line = 'No records found.';
        foreach ($result as $key => $value) {
            if ($key == array_key_first($result)) {
                $combined = array_combine($colWidths, ['id' => 'ID'] + array_keys($value));
                $line = '+';
                $str = '|';
                foreach ($combined as $w => $v) {
                    $line .= self::padString('', $w, '-')."+";
                    $str .= self::padString(' '.ucfirst($v), $w)."|";
                }
                echo $line."\n";
                echo $str."\n";
                echo $line."\n";
            }
            $combined = array_combine($colWidths, ['id' => $key] + $value);
            $str = '|';
            foreach ($combined as $w => $v) {
                $str .= self::padString(' '.$v, $w)."|";
            }
            echo $str."\n";
        }
        echo $line."\n";
    }

    private static function import(array $params): bool
    {
        Validator::validate($params, [
            'file' => ['required' => null, 'string' => ['min' => 5]]
        ]);

        if (!is_file(self::FILENAME)) {
            file_put_contents(self::FILENAME, json_encode([], JSON_THROW_ON_ERROR));
        }

        $data = self::getData();

        $fp = fopen(self::getFullPath($params['file']), 'r');
        if (!$fp) {
            return false;
        }

        $header = fgetcsv($fp);
        if (!$header || count($header) !== 6) {
            echo "Corrupt import file \"$params[file]\".\n";
            return false;
        }
        array_shift($header);

        $new = $dup = 0;
        while (($import = fgetcsv($fp)) !== false) {
            $id = $import[0];
            if (isset($data[$id])) {
                $dup++;
            } else {
                $data[$id] = [];
                array_shift($import);
                for ($i=0; $i < count($header); $i++) { 
                    $data[$id][$header[$i]] = $import[$i];
                }
                $new++;
            }
        }

        fclose($fp);
        echo "Imported $new new record(s), found $dup duplicate(s).\n";
        return self::putData($data);
    }

    private static function export(array $params): bool
    {
        Validator::validate($params, [
            'file' => ['required' => null, 'string' => ['min' => 5]]
        ]);

        $fp = fopen(self::getFullPath($params['file']), 'wb');
        if (!$fp) {
            return false;
        }

        $data = self::getData();
        if (!$data) {
            echo "Empty data. Nothing to export.\n";
            return false;
        }

        echo "Exporting ".count($data)." records...\n";

        fputcsv($fp, ['id' => 'id'] + array_keys($data[array_key_first($data)]));
        foreach ($data as $key => $value) {
            fputcsv($fp, ['id' => $key] + $value);
        }

        return fclose($fp);
    }

    private static function getFullPath(string $fileName): string
    {
        return dirname(__DIR__).DIRECTORY_SEPARATOR.$fileName;
    }

    private static function getData(): array
    {
        $path = self::getFullPath(self::FILENAME);
        if(!is_file($path)) {
            throw new \ErrorException("Cannot open file \"$path\"\n");
        }

        return json_decode(file_get_contents($path), true, 8, JSON_THROW_ON_ERROR);
    }

    private static function putData(array $data): int|bool
    {
        return file_put_contents(
            self::getFullPath(self::FILENAME),
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );
    }

    private static function padString(string $str, int $len, string $by = ' '): string
    {
        return mb_strimwidth($str, 0, $len) . str_pad('', $len - mb_strlen($str), $by);
    }
}
