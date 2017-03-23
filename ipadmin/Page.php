<?php

/**
 * Used for generate the Page-Selector,
 * Just for the request with GET method
 */
class Page
{
    public $totalRecord;    // total records
    public $showRows;    // the row number of each page shows
    public $totalPage;    // total page
    public $currentPage;    // current page
    public $baseUrl;    // iteration of the URL
    public $parameter;    // the parameters of each URL need to carry

    public function __construct($totalRecord, $showRows, $baseUrl, $parameter = array())
    {
        $this->totalRecord = $totalRecord;
        $this->showRows = $showRows;
        $this->totalPage = ceil($totalRecord / $showRows);
        $this->baseUrl = $baseUrl;
        $this->parameter = $parameter;
        empty($_GET['page']) ? $this->currentPage = 1 : $this->currentPage = $_GET['page'];
    }

    /**
     * generate the url of each Page-Selector button
     * @param int $page
     * @return string
     */
    private function generateUrl($page = 1)
    {
        $url = $this->baseUrl . '?';
        if (!empty($this->parameter)) {
            foreach ($this->parameter as $key => $value) {
                $url .= $key . '=' . $value . '&';
            }
        }
        $url .= 'page=' . $page;

        return $url;
    }

    /**
     * render the html for Page-Selector
     * @return string
     */
    public function show()
    {
        $html = '<div class="page_selector">';

        $startPage = $this->currentPage - 5;
        $startPage > 1 ? $html .= '<span><a href="' . $this->generateUrl(1) . '">1</a></span><span class="more_page">...</span>'
            : $startPage = 1;
        for ($i = $startPage; $i < $this->currentPage; $i++) {
            $html .= '<span><a href="' . $this->generateUrl($i) . '">' . $i . '</a></span>';
        }

        $html .= '<span><div class="current_page">' . $this->currentPage . '</div></span>';

        $endPage = $this->currentPage + 5;
        $endPage < $this->totalPage ? '' : $endPage = $this->totalPage;
        for ($i = $this->currentPage + 1; $i <= $endPage; $i++) {
            $html .= '<span><a href="' . $this->generateUrl($i) . '">' . $i . '</a></span>';
        }
        $endPage != $this->totalPage ? $html .= '<span class="more_page">...</span><span><a href="' . $this->generateUrl($this->totalPage)
            . '">' . $this->totalPage . '</a></span>' : '';

        return $html . '<span><div class="total_page">total ' . $this->totalPage . ' page</div></span></div>';
    }
}