<?php
	/*
	   Author:       Joyce Johnston
	   Date:         February 17, 2009
       Last Updated: June 9, 2009
	   Website:      Uncontenio.us
	*/
	class Page {
		
		private $requested_page;
		private $rows_per_page;
		private $offset;
		private $total_pages;
		private $query;
		private $url;
		private $div;
		private $default_col = 'genus';
		private $limit;
        private $jump;
        
		public function __construct($query, $url, $requested_page, $div,$options) {
    
            //set options
            $this->limit = $options['limit'] == '' ? $this->total_pages : $options['limit'];
            $this->jump = $options['jump'] == '' ? 10 : $options['jump'];
            $this->rows_per_page = $options['rows_per_page'] == '' ? 25 : $options['rows_per_page'];
			
            $this->query = $query;
			$this->url = $url;
			$this->total_pages = ceil($this->countRecords() / $this->rows_per_page);
			$this->div = $div;
            
            //make sure requested page is a positive integer less than the total number of pages in size
			$requested_page = (is_numeric($requested_page) ? floor(abs($requested_page)) : 1); 
			$this->requested_page = ($requested_page > $this->total_pages ? $this->total_pages : $requested_page); 
			$this->offset = ($this->requested_page-1) * $this->rows_per_page;
           
		}
		
		public function countRecords() {
			//returns the number of records
			global $db;
			$count_query = "SELECT COUNT(*) FROM (".$this->query.") as temp";
			$result = $db->query($count_query);
			$row = $result->fetch_row();
			return $row[0];
		}
		
		public function getPages() {
			//returns the number of pages
			return $this->total_pages;
		}
		
		public function getPage() {
			//gets the requested page
			global $db;
			$query = $this->query;
			$query .= ' LIMIT '.$this->offset.', '.$this->rows_per_page;
			$result = $db->query($query);
			return $result;
		}
		
		public function getPageNav($limit='',$jump = 10) {
			//returns the html markup for the pagination navigation
            
			$markup = '<ul class="pagination-nav">';
            $markup .= '<li><a class="previous-plus" href="'.$this->url.'?col='.$this->default_col.'&page='.($this->requested_page<=$this->jump ? 1 : $this->requested_page - $this->jump).'">&laquo;</a></li>';
			$markup .= '<li><a class="previous" href="'.$this->url.'?col='.$this->default_col.'&page='.($this->requested_page==1 ? $this->total_pages : $this->requested_page-1).'">Previous</a></li>';
			for($i=1;$i<=$this->total_pages;$i++) {
                //if($i >= $this->requested_page - $limit && $i <= $this->requested_page + $limit) {
                    $markup.=	'<li';
                    $markup.= ($i >= $this->requested_page - $this->limit && $i <= $this->requested_page + $this->limit ? '' : ' class="hide" ');
                    $markup.='><a class="page-'.$i.' ';
                    $markup.= ($i == $this->requested_page ? ' current" ' : '"');
                    $markup.='href="'.$this->url.'?col='.$this->default_col.'&page='.$i.'">'.$i.'</a></li>';
                //}
                
			} 
			$markup.= '<li><a class="next" href="'.$this->url.'?col='.$this->default_col.'&page='.($this->requested_page==$this->total_pages ? '1' : $this->requested_page+1).'">Next</a></li>';	
			$markup.= '<li><a class="next-plus" href="'.$this->url.'?col='.$this->default_col.'&page='.($this->requested_page + $this->jump >= $this->total_pages ? $this->total_pages : $this->requested_page + $this->jump).'">&raquo;</a></li>';
            $markup.= '</ul>';
			return $markup;
		}
		
		public function getScript() {
			//returns the javascript for pagination by ajax
			//requires mootools 1.2 core
			$script = '<script type="text/javascript">
                        window.addEvent("domready", function() {
                            var paginate = new Request.HTML({
                                update: "'.$this->div.'"
                            });	
                            $$(".pagination-nav").each(function(nav) {
                                var links = nav.getElements("a");
                                //var lis = nav.getElements("li");
                                links.each(function(link,index) {
                                    link.addEvent("click",function(e) {
                                        e.stop();
                                        var url = link.getProperty("href");
                                        var base = url.split("?");
                                        var ps = url.split("=");
                                        var p = ".page-"+ps[2];
                                        $$(".current").each(function(el) { 
                                            el.removeClass("current");
                                        });
                                        //hide and show page numbers as needed
                                        $$(".pagination-nav").each(function(n) {
                                            var lis = n.getElements("li");
                                            lis.each(function(el,i) {
                                                el.addClass("hide");
                                                if(i >= parseInt(ps[2]) + 1 - '.$this->limit.' && i <= parseInt(ps[2]) + 1 + '.$this->limit.') {
                                                    el.removeClass("hide");
                                                }
                                                if(i <= 1 || i >= '.$this->total_pages.'+ 2) {
                                                    el.removeClass("hide");
                                                }
                                                
                                            });
                                        });
                                        $$(p).each(function(pg) {
                                                pg.addClass("current");
                                            });
                                        //hide and show the correct pages
                                        
                                        //build the next and previous urls
                                        var col=$$(".cur-col")[0].getProperty("id");
                                        if(ps[2] == '.$this->total_pages.') {
                                            $$(".next").each(function(n) {
                                                n.setProperty("href",base[0]+"?col="+col+"&page=1");
                                            });	
                                        }
                                        else {
                                            $$(".next").each(function(n) {
                                                n.setProperty("href",base[0]+"?col="+col+"&page="+(parseInt(ps[2])+1));
                                            });
                                        }
                                        if(ps[2] == 1) {
                                            $$(".previous").each(function(p) {
                                                p.setProperty("href",base[0]+"?col="+col+"&page='.$this->total_pages.'");
                                            });	
                                        }
                                        else {
                                            $$(".previous").each(function(p) {
                                                p.setProperty("href",base[0]+"?col="+col+"&page="+(parseInt(ps[2])-1));
                                            });	
                                        }
                                        //build the next-plus and previous-plus urls
                                        if (parseInt(ps[2]) + '.$this->jump.' >= '.$this->total_pages.') { 
                                            $$(".next-plus").each(function(np) {
                                                np.setProperty("href",base[0]+"?col="+col+"&page='.$this->total_pages.'");
                                            });
                                        }
                                        else {
                                            $$(".next-plus").each(function(np) {
                                                np.setProperty("href",base[0]+"?col="+col+"&page="+(parseInt(ps[2])+'.$this->jump.'));
                                            });
                                        }
                                        if (ps[2] - '.$this->jump.' <= 1) { 
                                            $$(".previous-plus").each(function(pp) {
                                                pp.setProperty("href",base[0]+"?col="+col+"&page=1");
                                            });
                                        }
                                        else {
                                            $$(".previous-plus").each(function(pp) {
                                                pp.setProperty("href",base[0]+"?col="+col+"&page="+(parseInt(ps[2])-'.$this->jump.'));
                                            });
                                        }
                                        
                                        paginate.get(url);
                                    });
                                });
                            });
                            $$(".column").each(function(item) {
										item.addEvent("click",function() {
											var id = item.getProperty("id");
											var cur_page = $$(".current")[0].getProperty("href");
											var url = cur_page + "&col="+id;
											if(item.hasClass("desc")) {
												url = url +"&order=desc";
												item.removeClass("desc");
											}
											else { item.addClass("desc");}
											paginate.get(url);
											$$(".column").each(function(el) { el.removeClass("cur-col")});
											item.addClass("cur-col");
											//change the col on all the page links
											$$(".pagination-nav").each(function(nav) {
												var links = nav.getElements("a");
												links.each(function(item) {
													var href = item.getProperty("href");
													var s = href.split("=");
													var b = s[1].split("&");
													b[0] = id;
													s[1] = b.join("&");
													href =  s.join("=");
													item.setProperty("href",href);
												});
											});
										});
										item.addEvent("mouseover", function() {
											item.addClass("col-mo");
										});
										item.addEvent("mouseout", function() {
											item.removeClass("col-mo");
										});
									});
                        });
                    </script>';
			return $script;
		}
		
		public static function isAjax() {
		  return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
		}
	}
?>
