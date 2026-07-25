import { Component } from '@angular/core';
import { TimeSeriesChart } from './components/time-series-chart/time-series-chart';
import { BarChart } from './components/bar-chart/bar-chart';
import { SatisfactionChart } from './components/satisfaction-chart/satisfaction-chart';

@Component({
  selector: 'app-root',
  imports: [TimeSeriesChart, BarChart, SatisfactionChart],
  templateUrl: './app.html',
  styleUrl: './app.css',
})
export class App {}
