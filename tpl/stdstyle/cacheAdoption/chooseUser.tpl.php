
<div class="content2-pagetitle">
    <img src="tpl/stdstyle/images/blue/email.png" class="icon32" align="middle" alt="email" />
    {{adopt_04}}
    <a href="viewcache.php?cacheid={cacheid}">{cachename}</a>
</div>

<form action="chowner.php?action=addAdoptionOffer" method="post">
    <div>
      <p>{{adopt_05}}</p>
    </div>

    <div class="alertMsg">
      <p>{{adopt_06}}</p>
    </div>

    <div>
      <p>
        <label for="username">{{adopt_07}}</label>
        <input id="username" type="text" size="20" name="username" />
        <input type="submit" class="btn btn-sm btn-primary" value="{{adopt_08}}" />
      </p>
    </div>

    <input type="hidden" name ="cacheid" value="{cacheid}" />
</form>
