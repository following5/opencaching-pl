
<div class="content2-pagetitle">
    <img src="/images/blue/email.png" class="icon32" align="middle" alt="email" />
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
        <label for="username">{{adopt_07}}</label>
        <input id="username" type="text" size="25" name="username" />
        <input type="submit" class="btn btn-sm btn-primary" value="{{adopt_08}}" />
    </div>

    <input type="hidden" name ="cacheid" value="{cacheid}" />
</form>