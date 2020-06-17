/**
* Order by fulltext results, needs to be called with filterByFulltext method, otherwise we wont have columns to order by
*/
public function orderByFulltext(string $order = Criteria::ASC): self
{
    if ($order === Criteria::ASC) {
        <?php
            foreach ($columns as $key => $weight) {
                echo '$this->addAscendingOrderByColumn("fulltext_' . $key . ' * ' . $weight . '");';
            }
        ?>
    } else {
        <?php
            foreach ($columns as $key => $weight) {
                echo '$this->addDescendingOrderByColumn("fulltext_' . $key . ' * ' . $weight . '");';
            }
        ?>
    }

    return $this;
}