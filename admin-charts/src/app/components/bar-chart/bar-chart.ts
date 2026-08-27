import { Component, ElementRef, Input, OnDestroy, OnInit, ViewChild, signal } from '@angular/core';
import Chart from 'chart.js/auto';
import { DashboardDataService, PontoDado } from '../../services/dashboard-data.service';

@Component({
  selector: 'app-bar-chart',
  imports: [],
  templateUrl: './bar-chart.html',
  styleUrl: './bar-chart.css',
})
export class BarChart implements OnInit, OnDestroy {
  @Input() titulo = '';
  @Input() fonte: 'volume' | 'categoria' | 'loja-visitas-produto' = 'volume';
  @Input() horizontal = false;
  @Input() cor = '#f2f0ec';

  @ViewChild('canvas') canvasRef!: ElementRef<HTMLCanvasElement>;

  carregando = signal(true);

  private chart: Chart | null = null;
  private pollTimer: ReturnType<typeof setInterval> | null = null;

  constructor(private dados: DashboardDataService) {}

  ngOnInit(): void {
    this.carregar();
    this.pollTimer = setInterval(() => this.carregar(true), 5000);
  }

  ngOnDestroy(): void {
    this.chart?.destroy();
    if (this.pollTimer) clearInterval(this.pollTimer);
  }

  private carregar(silencioso = false): void {
    if (!silencioso) this.carregando.set(true);

    const fontes = {
      'volume': () => this.dados.volumeCompras(),
      'categoria': () => this.dados.vendasPorCategoria(),
      'loja-visitas-produto': () => this.dados.lojaVisitasProduto(),
    };
    const fonteObs = fontes[this.fonte]();

    fonteObs.subscribe({
      next: (pontos) => {
        this.desenhar(pontos);
        this.carregando.set(false);
      },
      error: () => this.carregando.set(false),
    });
  }

  private desenhar(pontos: PontoDado[]): void {
    if (!this.canvasRef) return;

    const labels = pontos.map((p) => p.label);
    const valores = pontos.map((p) => Number(p.valor));

    if (this.chart) {
      this.chart.data.labels = labels;
      this.chart.data.datasets[0].data = valores;
      this.chart.update();
      return;
    }

    this.chart = new Chart(this.canvasRef.nativeElement, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            data: valores,
            backgroundColor: this.cor,
            borderRadius: 3,
            maxBarThickness: 42,
          },
        ],
      },
      options: {
        indexAxis: this.horizontal ? 'y' : 'x',
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 400 },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#141416',
            borderColor: '#3a3a40',
            borderWidth: 1,
            titleColor: '#f2f0ec',
            bodyColor: '#a8a8ae',
            padding: 10,
          },
        },
        scales: {
          x: {
            grid: { color: '#2c2c30' },
            ticks: { color: '#6d6d73', font: { size: 10, family: 'Arial' } },
            beginAtZero: !this.horizontal,
          },
          y: {
            grid: { color: '#2c2c30' },
            ticks: { color: '#6d6d73', font: { size: 10, family: 'Arial' } },
            beginAtZero: this.horizontal,
          },
        },
      },
    });
  }
}
