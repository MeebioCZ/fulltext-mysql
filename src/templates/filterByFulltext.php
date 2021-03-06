/**
* Filter by fulltext columns
*/
public function filterByFulltext(string $string): self
{
    $connection = Propel::getConnection();
    <?php
        $i = 0;
        // First add columns to select query
        foreach ($columns as $column) {
            echo '
                $this->withColumn("MATCH(' . $column . ') AGAINST(" . $connection->quote($string) . " IN BOOLEAN MODE)", "fulltext_' . $i . '");
            ';

            $i++;
        }

        // Now lets add where
        $i = 0;
        echo "\$this->having('";
        foreach ($columns as $column) {
            if ($i > 0) {
                echo " + fulltext_$i";
            } else {
                echo "fulltext_$i";
            }

            $i++;
        }
        echo " > 0');";
    ?>

    return $this;
}
