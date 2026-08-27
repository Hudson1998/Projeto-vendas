import { Component } from '@angular/core';
import { TimeSeriesChart } from '../components/time-series-chart/time-series-chart';
import { BarChart } from '../components/bar-chart/bar-chart';

/**
 * Raiz do painel do lojista.
 *
 * Reaproveita os mesmos componentes de grafico do admin -- eles ja aceitam a
 * fonte por Input, e os endpoints da loja devolvem o mesmo {label, valor}.
 * Um segundo projeto Angular so para isso duplicaria toolchain e bundle sem
 * ganhar nada.
 */
@Component({
  selector: 'app-loja-root',
  imports: [TimeSeriesChart, BarChart],
  templateUrl: './loja-app.html',
})
export class LojaApp {}
