/**
 * Order by fulltext results, needs to be called with filterByFulltext method, otherwise we wont have columns to order by
 */
public static function computeFulltextValues(array $arr): float
{
    $result = 0;
    foreach ($arr as $key => $value) {
        $temp = explode('_', $key);
        if (count($temp) < 2 || $temp[0] === 'fulltext') {
            continue;
        }

        $result += $value * $weights[$temp[1]] ?? 0;
    }

    return $result;
}