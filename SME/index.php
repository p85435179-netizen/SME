<?php
// Compatibility for existing bookmarks. The frontend is served from index.html.
header('Location: index.html', true, 302);
exit;
