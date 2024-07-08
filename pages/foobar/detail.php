detail!

<hr>

<?php

d($_GET);
/*
d(
    CurrentRequest::getPage()->getName(),
    CurrentRequest::getPage()->getFilePath()
);
d(
    CurrentPage::getName(),
    CurrentPage::getFilePath()
);
if(CurrentPage::isDetailPage()) {
    d(
        CurrentRequest::getDetailPageQuery(),
    );
}
else {
    echo 'page interpreted as regular page rather then detail page because query is missing';
    // ... we could do a redirect here
}
*/
