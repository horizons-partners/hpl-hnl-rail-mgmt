# acl.auth.php
# <?php exit()?>
# Don't modify the lines above
#
# Access Control Lists
#
# Auto-generated by install script
# Date: Thu, 08 Aug 2019 12:13:42 +0000
*	@ALL	0
*	@user	1
depot:*	@staff	1
general:*	@staff	1
main	@staff	1
occ:*	@staff%5focc	1
ptw:*	@staff	1
station:*	@staff%5focc	1
station:*	@staff%5fstation	1
train:*	@staff%5focc	1
train:*	@staff%5ftrain	1
wiki:*	@ALL	1
*	hrh%5ftraining	1
playground:*	hrh%5ftraining	0
*	hpl%5fteam	16
