import { UserPlus, Settings, CreditCard } from "lucide-react";

const steps = [
  {
    icon: UserPlus,
    title: "Regístrate",
    description: "Crea tu cuenta en menos de 2 minutos",
  },
  {
    icon: Settings,
    title: "Configura",
    description: "Personaliza tus opciones de cobro",
  },
  {
    icon: CreditCard,
    title: "Cobra",
    description: "Recibe pagos automáticamente",
  },
];

export const HowItWorks = () => {
  return (
    <div className="py-20">
      <div className="container mx-auto px-6">
        <h2 className="text-3xl font-bold text-center mb-12">
          ¿Cómo funciona?
        </h2>
        <div className="grid md:grid-cols-3 gap-8">
          {steps.map((step, index) => (
            <div key={step.title} className="text-center relative">
              {index < steps.length - 1 && (
                <div className="hidden md:block absolute top-8 right-0 w-full h-0.5 bg-primary/20" />
              )}
              <div className="inline-block p-4 bg-primary rounded-full mb-4 relative z-10">
                <step.icon className="w-8 h-8 text-white" />
              </div>
              <h3 className="text-xl font-semibold mb-2">{step.title}</h3>
              <p className="text-gray-600">{step.description}</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};