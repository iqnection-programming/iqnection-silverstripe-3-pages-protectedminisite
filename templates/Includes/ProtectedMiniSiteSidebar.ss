<% with FindTopParent %>
	<div id="minisite_sidebar" class="fullheight">
		<a href="$Link" class="$LinkingMode"><h2>$Title</h2></a>
		<a href="$FindTopParent.Link(logout)" id="minisite_logout">Logout</a>
		<ul>
		<% loop Children %>
			<% if CanAccess %>
				<li>
					<a href="$Link" class="$LinkingMode">$Title</a>
				</li>
			<% end_if %>
		<% end_loop %>
		</ul>
	</div><!--minisite_sidebar-->
<% end_with %>