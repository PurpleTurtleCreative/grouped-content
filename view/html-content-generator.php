<?php
/**
 * Content Generator form page
 *
 * A form that allows users to rapidly create content.
 *
 * @since 1.1.0
 */

declare(strict_types=1);

namespace ptc_grouped_content;

defined( 'ABSPATH' ) || die();

global $ptc_grouped_content;
?>

<header>
  <h1>Content Generator</h1>
</header>

<div id="content-generator">

  <main>

    <?php require_once $ptc_grouped_content->plugin_path . 'src/script-process-content-generator.php'; ?>

    <form method="POST">

      <section id="parent-page">

        <h2>Parent Page</h2>

        <div id="select-parent-page" class="form-group">
          <label for="parent-page-id">Parent Page</label>
          <select id="parent-page-id" name="parent_page_id" required>
            <option value="-1" selected>(create new page)</option>
            <option value="0">(no parent)</option>
            <?php
            $dropdown_args = [
              'post_type'   => 'page',
              'sort_column' => 'menu_order,post_title',
              'post_status' => 'publish,draft,private',
              'echo'        => FALSE,
            ];
            $pages_dropdown_html = wp_dropdown_pages( $dropdown_args );
            echo preg_replace( '/<.*select.*>/i', '', $pages_dropdown_html );
            ?>
          </select>
        </div>

        <div id="create-new-parent-page" class="form-group">
          <label for="new-parent-page-title">Page Title</label>
          <input id="new-parent-page-title" type="text" name="new_parent_page_title" />
        </div>

      </section>

      <section id="children-pages">

        <h2>Child Pages</h2>

        <div class="form-group">
          <label for="children-page-titles">Page Titles</label>
          <textarea id="children-page-titles" name="children_page_titles" required
            placeholder="Enter each page title on a separate line..."></textarea>
        </div>

      </section>

      <section id="menu">

        <h2>Menu</h2>

        <div class="form-group">
          <label for="create-menu">Generate menu for this group</label>
          <input id="create-menu" type="checkbox" name="create_menu" value="yes" checked>
        </div>

      </section>

      <section id="content-generator-submit">
        <input type="hidden" name="generate_content_nonce" value="<?php echo esc_attr( wp_create_nonce( 'generate_content' ) ); ?>" />
        <input type="submit" name="generate_content" value="Generate" />
      </section>

    </form>

  </main>

  <aside>

    <section id="help-box">

      <h2><i class="far fa-life-ring"></i>Need Help?</h2>

      <div>

        <p class="help-default"><i class="fas fa-mouse-pointer"></i> Hover or click on a form element to learn more.</p>

      </div>

    </section>

  </aside>

</div>
