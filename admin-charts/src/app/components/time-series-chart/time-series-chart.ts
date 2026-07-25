import { Component, ElementRef, Input, OnDestroy, OnInit, ViewChild, signal } from '@angular/core';
import Chart from 'chart.js/auto';
import { DashboardDataService, Granularidade, PontoDado } from '../../services/dashboard-data.service';

@Component({
  selector: 'app-time-series-chart',
  imports: [],
  templateUrl: './time-series-chart.html',
  styleUrl: './time-series-chart.css',
})
export class TimeSeriesChart implements OnInit, OnDestroy {
  @Input() titulo = '';
  @Input() tipo: 'acessos' | 'receita' = 'acessos';
  @Input() cor = '#f2f0ec';

  @ViewChild('canvas') canvasRef!: ElementRef<HTMLCanvasElement>;

  granularidade = signal<Granularidade>('dia');
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

  mudarGranularidade(g: Granularidade): void {
    if (this.granularidade() === g) return;
    this.granularidade.set(g);
    this.carregar();
  }

  private carregar(silencioso = false): void {
    if (!silencioso) this.carregando.set(true);

    const fonte = this.tipo === 'acessos' ? this.dados.acessos(this.granularidade()) : this.dados.receita(this.granularidade());

    fonte.subscribe({
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
    const valores = pontos.map((p) => p.valor);

    if (this.chart) {
      this.chart.data.labels = labels;
      this.chart.data.datasets[0].data = valores;
      this.chart.update();
      return;
    }

    this.chart = new Chart(this.canvasRef.nativeElement, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            data: valores,
            borderColor: this.cor,
            backgroundColor: this.cor + '22',
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: this.cor,
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 400 },
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#141416',
            borderColor: '#3a3a40',
            borderWidth: 1,
            titleColor: '#f2f0ec',
            bodyColor: '#a8a8ae',
            padding: 10,
            callbacks: {
              label: (ctx) =>
                this.tipo === 'receita'
                  ? 'R$ ' + Number(ctx.parsed.y).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                  : ctx.parsed.y + ' acessos',
            },
          },
        },
        scales: {
          x: {
            grid: { color: '#2c2c30' },
            ticks: { color: '#6d6d73', font: { size: 10, family: 'Arial' }, maxRotation: 0, autoSkip: true },
          },
          y: {
            beginAtZero: true,
            grid: { color: '#2c2c30' },
            ticks: { color: '#6d6d73', font: { size: 10, family: 'Arial' } },
          },
        },
      },
    });
  }
}
