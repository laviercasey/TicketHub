<h1>Upgrade completed!</h1>
<P>Congratulations your new support ticket system is now upgraded. Please refer to Release Notes for more information about changes and/or new features.</p>
<h3>Post-upgrade Setup</h3>
Please take a minute to cleanup!
<ul>
    <li><strong>Delete setup directory</strong><br>
     After verifying that the upgrade completed correctly please delete setup folder.</li>
    <?php
     if(!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {?>
     <li><strong>Install/Enable OpenSSL extension (optional)</strong><br>
       OpenSSL extension is not enabled or installed. IMAP/POP passwords will be stored as plain text in database.
     </li>
     <?php
     }?>
    <li><strong>Enable the helpdesk</strong><br>
     As admin you can enable it in <a href="../scp/admin.php" target="_blank">Admin Panel</a> - preference section.
</ul>
<h3>Commercial Support Available</h3>
<p>Don't let technical problems impact your customer support experience. Get guaranteed response times and access to the core development team.
We can also customize the system to meet your unique needs. For more information or to discuss your needs, please <a href="https://github.com/LaverCasey/TicketHub" target="_blank">contact us today</a></p>

<p>Thank you for choosing TicketHub and best of luck.<br><br>TicketHub Team.</p>
