/**
* Order by fulltext results, needs to be called with filterByFulltext method, otherwise we wont have columns to order by
*/
public function orderByFulltext(string $order = Criteria::ASC): self
{
    if ($order === Criteria::ASC) {
        $this->addAscendingOrderByColumn("(
        <?php
            $i = 0;
            foreach ($columns as $key => $weight) {
                if ($i === 0) {
                    echo "Fulltext_$key * $weight";
                } else {
                    echo " + Fulltext_$key * $weight";
                }

                $i++;
            }
        ?>
        )");
    } else {
        $this->addDescendingOrderByColumn("(
        <?php
            $i = 0;
            foreach ($columns as $key => $weight) {
                if ($i === 0) {
                    echo "Fulltext_$key * $weight";
                } else {
                    echo " + Fulltext_$key * $weight";
                }
                
                $i++;
            }
        ?>
        )");
    }

    return $this;
}