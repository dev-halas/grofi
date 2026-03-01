  <?php wp_footer(); ?>
  <footer class="footer">
    <div class="container">
      <div class="footerHead flex space-between">
        <div class="footerLogo">
          <img src="<?php echo THEME_URL; ?>/_dev/assets/logo.svg" alt="Grofi Store">
        </div>
        <div class="footerSocial">
          <a href="#" target="_blank" class="footerSocialItem">
            <img src="<?php echo THEME_URL; ?>/_dev/assets/icons/facebook.svg" alt="Facebook">  
          </a>
          <a href="#" target="_blank" class="footerSocialItem">
            <img src="<?php echo THEME_URL; ?>/_dev/assets/icons/instagram.svg" alt="Instagram">
          </a>
        </div>
      </div>
      <div class="footerContent">
        <div>
          <p class="mb-16">
            <strong>ul. Baryczna 48</strong><br/>
            <strong>63-400 Wysocko Wielkie</strong>
          </p>
          <ul class="list-style-none">
            <li>Poniedziałek – Piątek: 8:00-17:00</li>
            <li>Sobota - Nieczynne</li>
            <li>Niedziela - Nieczynne</li>
          </ul>
        </div>
        <div class="footerMenuContainer">
          <div class="footerMenu">
            <span><?php echo get_menu_name_by_location('footer_nav_1'); ?></span>
            <?php wp_nav_menu(array(
              'theme_location' => 'footer_nav_1',
            )); ?>
          </div>
          <div class="footerMenu">
            <span><?php echo get_menu_name_by_location('footer_nav_2'); ?></span>
            <?php wp_nav_menu(array(
              'theme_location' => 'footer_nav_2',
            )); ?>
          </div>
          <div class="footerMenu">
            <span><?php echo get_menu_name_by_location('footer_nav_3'); ?></span>
            <?php wp_nav_menu(array(
              'theme_location' => 'footer_nav_3',
            )); ?>
          </div>
        </div>
      </div>
    </div>
  </footer>
</body>
</html>