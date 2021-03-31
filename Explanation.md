# Explanation File

## Introduction

### Summary

The administrator wants to have a visibility on the internal links of his website to improve his SEO. He must be able to execute on demand a crawl of his site to retrieve the current state of the internal mesh of his site, create a sitemap.html file accessible by visitors and create a snapshot of his homepage at the time of the crawl.

Once executed, the crawl is then launched automatically every hour.

During each execution, the sitemap.html and the snapshot of the homepage are reset.

### Context

The website runs on a PHP version equal to 7.0+, and a WordPress version equal to 5.2+.

## Solution

### Technology stack

Although I have the choice between a native PHP application and a WordPress plugin, the plugin choice is more appropriate.

It will let me take advantage of all the features provided by the CMS and will ensure a perfect integration of our application in the existing environment.

Moreover, it will facilitate among other things the management of security, scheduled tasks and notices.

### Technical decisions

#### General

- The plugin project is hosted and versioned on [Github](https://github.com/keyros67/wp-crawler). 

- The plugin structure is based on this [WordPress Plugin Boilerplate](https://github.com/DevinVinson/WordPress-Plugin-Boilerplate) recommended by WordPress. I made this choice because I think that it is not necessary to reinvent the wheel. This boilerplate is object-oriented and respects the coding and WordPress standards. It offers an optimal and secure file structure. The code of the admin part is separated from the public part.

- Internationalization is implemented, but the translation files have not been created.
  
- The name of the plugin is "WP Crawler". The prefix "wpc" can also be used to refer to it.
  
- WordPress has a "wp-content" folder dedicated to the files that do not belong to the core files. Therefore, I will use the "uploads" sub-folder to store the files created by the plugin.

- The plugin will store the data of the crawled pages in the database.

- The information stored in the database and in the "uploads" folder are deleted when the plugin is deleted.

- Notifications are displayed depending on the code execution.

#### Database

The data of the crawled pages are stored in the "wpcrawler" table. The data collected for each page consist in a unique id, the parent page id, the url and the title.

The type of the page id is defined as bigint, just like the wordpress post id. Our page id is not the post id but an autoincremented integer. But if WordPress uses the bingint type, theoretically we can have at least that many pages.

#### Third-party libraries

The plugin includes 2 externals libraries:

- [Simple HTML DOM](https://sourceforge.net/projects/simplehtmldom/files/latest/download): I included this library to simplify access to the dom of the web pages. It was not mandatory, but it helped me develop the plugin more easily.


- [TreeViewJs](https://github.com/samarsault/TreeViewJS): JavaScript library to quickly implement a dynamic Tree View element.


### How it works

#### Plugin activation

Static function `Wp_Crawler_Activator::activate()`

The "wpcrawler" table is created when the plugin is activated. It allows storing the data of the crawled internal pages.
I chose to store the data in a dedicated table in order not to overload the WordPress options table and slow down the site, although the impact on a tiny number of pages is minimal. However, in a forecast of site development, it is better to have a dedicated table.

Class `Wp_Crawler`

The Wp_Crawler class is instantiated: it loads the dependencies, defines the hooks and adds the plugin administration page in the menu for admin users (Settings > WP Crawler).

#### Plugin deactivation
Static function `Wp_Crawler_Deactivator::deactivate()`

Scheduled tasks are deleted when the plugin is deactivated, but the data is kept.

#### Plugin deletion

File `uninstall.php`

When deleting the plugin, the table and data in the database, scheduled tasks and files created by the plugin are removed.

#### Administration page

The dashboard page is displayed when you click on the link in the menu.

The page has a button to manually start the crawl of the site.

If a crawl has already been performed, the results of the last crawl are displayed as well as 2 buttons to open the sitemap.html file and the static file of the homepage.

*A 3rd button should be added to refresh the crawl results (with an ajax call) without relaunching the crawl. It is however possible to reload the results by clicking again on the link in the menu or by reloading the page.*

#### The crawl

When the crawl is launched, a set of functions is executed. I tried to split each action into a function as much as possible.

###### Crawl button

The crawl is executed by clicking on the submit button "Crawl Now!"
A token (nonce) is added to the form to verify the authenticity of the post request.

Once the verification is passed, 3 functions are called:

- The `crawl()` function which executes the crawl tasks (see description below).
- The `set_cron_task()` function which adds the crawl to the scheduled task every hour.
- The `get_crawl_results()` function which retrieves the results of the last crawl to display them on the dashboard page.

###### Crawl function

Function `WP_Crawler_Admin->crawl()`

The crawl function takes as parameter the starting page of the crawl. By default, the crawl is launched from the homepage of the site.

Then the following tasks are executed:

- It calls the function `delete_previous_results()` which deletes the records of the previous crawl by truncating the "wpcrawler" table.

- It saves the data of the starting page (in our case, the homepage) in the table "wpcrawler".

- It retrieves the content and lists the links of this page using the Simple HTML DOM library. The title of each page is retrieved using our `get_webpage_title()` function. This function uses a regular expression to get the title of each page in the DOM. Each internal url is inserted with his title in our database table.
 
  *I also thought of using the WordPress function url_to_postid to get the id of the page and therefore its title. But this function only works for pages created in WordPress and does not work on dynamic pages.*

- It calls the function `create_static_page()` which creates a html version of the homepage and saves it in the "uploads" folder.

- It calls the function  `delete_sitemap_html()` which deletes the sitemap.html file if exists.

- It calls the function `create_sitemap_html()` which creates the sitemap.html based on the crawl results (see description below).

- Return a success or error notification depending on the execution of the previous tasks by calling the `wpc_crawl_notice_success()` or `wpc_crawl_notice_error()` function.


###### Sitemap.html

Function `WP_Crawler_Admin->create_sitemap_html()`

The function retrieves all the internal urls found during the crawl, cleans the urls by removing the anchors, removes the duplicates and generates the sitemap.html file.

The sitemap.html file is saved in the "uploads" sub-folder in order to respect the WordPress recommendations. The user will be able to access the sitemap.html file transparently from the root url of the site thanks to a rewriting rule add th.

*The file is created from a template saved in one of the plugin folders. It might be more appropriate to create the file on the fly or to store the template in the options table.*

