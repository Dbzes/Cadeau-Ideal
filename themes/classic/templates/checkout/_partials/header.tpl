{block name='header_nav'}
  <nav class="header-nav">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-4 hidden-sm-down" id="_desktop_logo">
          <a href="{$urls.base_url}">
            <img src="/img/template/le-cadeau-ideal.png" alt="{$shop.name}" class="logo-desktop" />
          </a>
        </div>
        <div class="col-md-8 hidden-sm-down">
          <div class="header-right">
            <div class="header-right-search">
              {hook h='displayTop'}
            </div>
          </div>
        </div>
        <div class="col-xs-12 hidden-md-up mobile">
          <div class="float-xs-left" id="menu-icon">
            <i class="material-icons">&#xE5D2;</i>
          </div>
          <div class="top-logo" id="_mobile_logo">
            <a href="{$urls.base_url}">
              <img src="/img/template/le-cadeau-ideal.png" alt="{$shop.name}" />
            </a>
          </div>
          <div class="clearfix"></div>
          <div class="mobile-header-search">
            {hook h='displayTop'}
          </div>
        </div>
      </div>
    </div>
  </nav>
{/block}

{block name='header_top'}
  <div class="header-top hidden-md-up">
    <div class="container">
      <div id="mobile_top_menu_wrapper" class="row hidden-md-up" style="display:none;">
        <div class="js-top-menu mobile" id="_mobile_top_menu"></div>
        <div class="js-top-menu-bottom">
          <div id="_mobile_currency_selector"></div>
          <div id="_mobile_language_selector"></div>
          <div id="_mobile_contact_link"></div>
        </div>
      </div>
    </div>
  </div>
  {hook h='displayNavFullWidth'}
{/block}
