<div id="hp-news">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="page-header col-md-12" style="margin-top: 0px;">
                    <h1><div style="float: left;"><?=_l('heading_latest_news');?></div><div class="header-icon-container"><div class="header-icon" id="header-icon-news"></div></div></h1>
                </div>



                <ul>
                    <? foreach ($news as $item): ?>
                    <li>
                        <h3><?=$item->title?> <span class="date"><?=date('M j<\s\up>S</\s\up>, Y', $item->published / 1000)?></span></h3>
                        <?=$item->text?>
                    </li>
                    <? endforeach; ?>
                    <!--
                    <li>
                        <?php if ($this->language=='en'): ?>
                            <h3>Bitso exits beta!  <span class="date">July 18, 2014</span></h3>
                            We are proud to announce that Bitso has completed its beta period. We are the first service of our kind in the country, crafted for needs of Mexicans. As part of the official launch, we are also happy to announce a 10% referral scheme for our clients.
                        <?php else: ?>
                            <h3>Bitso sale de beta  <span class="date">18 Julio 2014</span></h3>
                            Estamos muy orgullosos de anunciar que Bitso finaliza su etapa de beta. Somos el primer servicio de este tipo en el país y es muy importante para nosotros ofrecer una plataforma especialmente diseñada para las necesidades de los mexicanos. Con este lanzamiento anunciamos nuestro Programa de Recompensas del 10% por referencias.
                        <?php endif ?>
                    </li>
                    <li>
                        <?php if ($this->language=='en'): ?>
                            <h3>Introducing Ripple  <span class="date">May 6, 2014</span></h3>
                            We are pleased to announce today the addition of Ripple funding and withdrawal options! You can withdraw MXN or XBT into the Ripple network. Please go to the funding and withdrawal pages to access the new feature.
                        <?php else: ?>
                            <h3>Introduciendo Ripple <span class="date">6 mayo 2014</span></h3>
                            Estamos muy contentos de anunciar la nueva adición para fondear tu cuenta usando Ripple! Ya puedes retirar MXN o XBT en el Ripple network. Favor de ir a las páginas de Retirar y Financiar Cuenta para acceder esta nueva funcionalidad de Bitso.
                        <?php endif ?>
                    </li>
                    <li>
                        <?php if ($this->language=='en'): ?>
                            <h3>Bitso goes live!  <span class="date">April 7, 2014</span></h3>
                            We are pleased to announce that today we open our doors launching a beta version of Bitso, the first Mexican Bitcoin exchage.  We also have another important announcement, one that you will love! During our beta period we are offering 0% commissions in all trades. You will be able to use our platform free of charge!
                        <?php else: ?>
                            <h3>Bitso entra en acción! <span class="date">7 abril 2014</span></h3>
                            Estamos muy emocionados de anunciar que hoy abrimos nuestras puertas lanzando la versión beta de Bitso, el primer Bitcoin exchange mexicano. Durante nuestro periodo de beta estamos ofreciendo 0% de comisiones en todos los trades. Vas a poder utilizar nuestra plataforma completamente gratis!
                        <?php endif ?>
                    </li>
                    <li>
                        <?php if ($this->language=='en'): ?>
                            <h3>Bitso Enters Beta Testing! <span class="date">Mar 25, 2014</span></h3>
                            We are pleased to announce that Bitso has officially entered the beta testing phase.
                        <?php else: ?>
                            <h3>Ya estamos en Beta! <span class="date">25 marzo 2014</span></h3>
                            Nos complace anunciar que Bitso ha entrado oficialmente en fase de pruebas beta.
                        <?php endif ?>
                    </li>
                    -->
                </ul>
            </div>

        </div>

    </div>
</div>